<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\GameProduct as Model;
use app\common\model\GameAccount;
use app\common\annotation\Permission;
use app\common\service\GameProductPriceService;
use app\common\service\GameProductOfferSyncService;
use app\common\service\GameProductPushService;
use think\facade\Log;

class GameProduct extends BaseController
{
    #[Permission(title: '游戏产品', isMenu: 1, parentUrl: 'user', isHideSub: 1)]
    public function index(): void
    {
        $lists = $this->scopeOwnedData(
            $this->tableList(Model::class, ['id' => 'DESC'], ['title', 'product_id'], [GameAccount::class]),
            'game_product'
        )
            ->with(['gameAccount'])
            ->selectData();
        if (!is_numeric($lists)) {
            $lists->each(function (Model $item) {
                $item->platform_name = GameAccount::$PLATFORM_MAP[$item->platform] ?? '';
                // 优先显示账号名称，没有名称则回退显示用户ID，账号被删/不存在时显示 '--'
                $item->account_display = $item->gameAccount
                    ? ($item->gameAccount->account_name ?: $item->gameAccount->user_id)
                    : '--';
                // 关联账号的平台：改价/同步走的都是账号平台，前端按钮显隐必须按这个判断，
                // 不能用产品自身的 platform（两者可能不一致）。账号不存在时为 0
                $item->account_platform = $item->gameAccount ? (int) $item->gameAccount->platform : 0;
                // 同步状态：offer_data 有值即视为已同步；G2G 没有这个接口，显示 '--'
                $item->offer_sync_text = $item->account_platform === GameAccount::PLATFORM_ELDORADO
                    ? (empty($item->offer_data) ? '未同步' : '已同步')
                    : '--';
            });
        }
        $this->success('', [
            'list' => $lists,
        ]);
    }

    /**
     * 新增产品。ELD 平台的产品新增后自动执行一次「同步线上数据」，
     * 直接把线上的价格/库存/币种和 offer_data 落库，省去手动点按钮那一步
     * （ELD 改价依赖 offer_data，没有它改不了价）。
     *
     * 同步失败（令牌失效/限流/网络异常等）不影响新增结果，只记日志，
     * 用户后续可手动点「同步线上数据」重试。
     */
    #[Permission(title: '添加游戏产品')]
    public function add(): void
    {
        $this->assertOwnedData('game_account', input('game_account_id'), '请选择当前账号名下的游戏账号');
        $this->mAdd(Model::class, [], [], function (Model $product) {
            $account = $product->gameAccount;
            if ($account && (int) $account->platform === GameAccount::PLATFORM_ELDORADO) {
                try {
                    GameProductOfferSyncService::sync($product);
                } catch (\Throwable $e) {
                    Log::warning('[GameProduct] 新增后自动同步线上数据失败 productId='
                        . $product->id . ': ' . $e->getMessage());
                }
            }
            return $product;
        });
    }

    #[Permission(title: '编辑游戏产品')]
    public function edit(): void
    {
        $id = input('id');
        $this->assertOwnedData('game_product', $id);
        $this->assertOwnedData('game_account', input('game_account_id'), '请选择当前账号名下的游戏账号');
        $original = Model::with(['gameAccount'])->find($id);
        if (!$original) {
            $this->error('产品不存在');
        }
        $originalStock = (int) $original->stock;
        $originalData = is_array($original->offer_data) ? $original->offer_data : [];
        $originalDescription = (string) ($originalData['details']['description'] ?? '');
        $data = $this->request->put();
        $description = array_key_exists('description', $data) ? (string) $data['description'] : $originalDescription;
        // 常规编辑不接受改价或直接改 offer_data；价格只能走改价入口。
        unset($data['price'], $data['description'], $data['offer_data']);
        $validate = new \app\common\validate\GameProduct();
        $data['id'] = $id;
        if (!$validate->scene('edit')->check($data)) {
            $this->error($validate->getError());
        }
        $account = GameAccount::find($data['game_account_id']);
        $isEldorado = $account && (int) $account->platform === GameAccount::PLATFORM_ELDORADO;
        $descriptionChanged = $isEldorado && $description !== $originalDescription;
        if ($descriptionChanged) {
            if (!$originalData) {
                $this->error('缺少线上产品资料，请先同步线上数据后再编辑描述');
            }
            $originalData['details']['description'] = $description;
            $data['offer_data'] = $originalData;
        }
        // 先保留 ERP 的期望值；平台请求失败时用户可按「推送平台」重试。
        $original->assocStore($data, []);
        if ($isEldorado && ($descriptionChanged || (int) $original->stock !== $originalStock)) {
            try {
                $result = GameProductPushService::push($original);
                $this->success('保存并推送成功', $result);
            } catch (\RuntimeException $e) {
                $this->error('ERP 已保存，但平台推送失败：' . $e->getMessage());
            }
        }
        $this->success('操作成功', $original->toArray());
    }

    #[Permission(title: '删除游戏产品')]
    public function delete(): void
    {
        $this->assertOwnedData('game_product', $this->getInputPk());
        $this->mDelete(Model::class);
    }

    public function get(): void
    {
        $this->success('', [
            'detail' => $this->scopeOwnedData(Model::with(['gameAccount']), 'game_product')->find(input('id')),
        ]);
    }

    #[Permission(title: '修改状态')]
    public function status(): void
    {
        $this->assertOwnedData('game_product', $this->getInputPk());
        $status = input('status', 0);
        Model::update(['status' => $status], ['id' => $this->getInputPk()]);
        $this->success('修改成功', ['status' => $status]);
    }

    /**
     * 改价：先用账号令牌换取 access_token（10分钟内复用缓存），再调用 G2G 改价接口，
     * 成功后同步更新本地价格。全程调用日志见 GameAccountApiLog。
     */
    #[Permission(title: '修改价格')]
    public function updatePrice(): void
    {
        $id = input('id');
        $price = input('price');
        if (!$id || $price === null || $price === '') {
            $this->error('参数不足');
        }
        $this->assertOwnedData('game_product', $id);
        $price = (float)$price;
        if ($price <= 0) {
            $this->error('价格必须大于0');
        }
        $product = Model::with(['gameAccount'])->find($id);
        if (!$product) {
            $this->error('产品不存在');
        }
        $oldProductId = (string) $product->product_id;
        try {
            // 与策略自动改价复用同一段内部逻辑（G2G 改价 + 同步本地价格）
            GameProductPriceService::change($product, $price);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
        $recreated = (string) $product->product_id !== $oldProductId;
        $message = $recreated
            ? sprintf('新增改价成功：product_id=%s（已删除旧 product_id=%s）', $product->product_id, $oldProductId)
            : '改价成功';
        $this->success($message, ['price' => $price, 'product_id' => $product->product_id]);
    }

    /**
     * 同步线上平台数据：拉取 ELD offer 详情，裁出需要的字段写入 offer_data，
     * 并把价格/库存/币种同步成线上现值。仅 Eldorado 平台支持，G2G 无此接口。
     */
    #[Permission(title: '同步线上数据')]
    public function syncOffer(): void
    {
        $id = input('id');
        if (!$id) {
            $this->error('参数不足');
        }
        $this->assertOwnedData('game_product', $id);
        $product = Model::with(['gameAccount'])->find($id);
        if (!$product) {
            $this->error('产品不存在');
        }
        try {
            $offerData = GameProductOfferSyncService::sync($product);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
        $this->success('同步成功', [
            'price'      => $product->price,
            'stock'      => $product->stock,
            'currency'   => $product->currency,
            'offer_data' => $offerData,
        ]);
    }

    /** 手动恢复或更新线上 offer；始终复用当前 ERP 产品行。 */
    #[Permission(title: '推送平台')]
    public function pushOffer(): void
    {
        $id = input('id');
        if (!$id) {
            $this->error('参数不足');
        }
        $this->assertOwnedData('game_product', $id);
        $product = Model::with(['gameAccount'])->find($id);
        if (!$product) {
            $this->error('产品不存在');
        }
        try {
            $result = GameProductPushService::push($product);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
        $this->success($result['created'] ? '已在平台创建产品' : '已更新平台产品', $result);
    }

    public function select(): void
    {
        $this->success('', [
            'list' => $this->scopeOwnedData($this->tableList(Model::class, [], ['title', 'product_id']), 'game_product')
                ->field('title as label,id as value')
                ->select()
        ]);
    }

    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id'],
            [
                'v' => 'account_display',
                'label' => '关联账号',
                'search' => 'game_account_id',
                'sort' => 'game_account_id',
                'searchType' => 'multiple',
                'searchList' => '/gameAccount/select',
            ],
            ['v' => 'product_id', 'label' => '产品ID'],
            ['v' => 'title', 'label' => '产品名称'],
            [
                'v' => 'platform_name',
                'search' => 'platform',
                'searchType' => 'multiple',
                'label' => '平台',
                'searchList' => GameAccount::getPlatformList(),
                'sort' => 'platform',
            ],
            ['v' => 'price', 'label' => '价格', 'searchType' => 'number', 'sort' => 'price'],
            ['v' => 'stock', 'label' => '库存', 'searchType' => 'number', 'sort' => 'stock'],
            ['v' => 'currency', 'label' => '货币'],
            ['v' => 'sold_count', 'label' => '已出售数', 'searchType' => 'number', 'sort' => 'sold_count'],
            ['v' => 'sales_amount', 'label' => '销售金额', 'searchType' => 'number', 'sort' => 'sales_amount'],
            // 线上数据同步状态：ELD 看 offer_data 有无值，G2G 恒为 '--'。
            // offer_data 是 JSON 字段，不做搜索/排序
            ['v' => 'offer_sync_text', 'label' => '同步状态', 'search' => false],
            ['v' => 'status', 'label' => '状态', 'render' => 'status', 'sort' => 'status'],
            ['v' => 'created_at', 'label' => '创建时间', 'search' => 'created_at', 'searchType' => 'daterange', 'sort' => 'created_at'],
        ];
    }
}
