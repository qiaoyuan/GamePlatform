<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\GameProduct as Model;
use app\common\model\GameAccount;
use app\common\annotation\Permission;
use app\common\service\G2gClient;

class GameProduct extends BaseController
{
    #[Permission(title: '游戏产品', isMenu: 1, parentUrl: 'user', isHideSub: 1)]
    public function index(): void
    {
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['title', 'product_id'], [GameAccount::class])
            ->with(['gameAccount'])
            ->selectData();
        if (!is_numeric($lists)) {
            $lists->each(function (Model $item) {
                $item->platform_name = GameAccount::$PLATFORM_MAP[$item->platform] ?? '';
                // 优先显示账号名称，没有名称则回退显示用户ID，账号被删/不存在时显示 '--'
                $item->account_display = $item->gameAccount
                    ? ($item->gameAccount->account_name ?: $item->gameAccount->user_id)
                    : '--';
            });
        }
        $this->success('', [
            'list' => $lists,
        ]);
    }

    #[Permission(title: '添加游戏产品')]
    public function add(): void
    {
        $this->mAdd(Model::class);
    }

    #[Permission(title: '编辑游戏产品')]
    public function edit(): void
    {
        // price 不允许在常规编辑中修改：改价需要同步 G2G 平台，只能走 updatePrice() 接口
        $this->mEdit(Model::class, ['except' => ['price']]);
    }

    #[Permission(title: '删除游戏产品')]
    public function delete(): void
    {
        $this->mDelete(Model::class);
    }

    public function get(): void
    {
        $this->success('', [
            'detail' => Model::with(['gameAccount'])->find(input('id')),
        ]);
    }

    #[Permission(title: '修改状态')]
    public function status(): void
    {
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
        $price = (float)$price;
        if ($price <= 0) {
            $this->error('价格必须大于0');
        }
        $product = Model::with(['gameAccount'])->find($id);
        if (!$product) {
            $this->error('产品不存在');
        }
        if (!$product->gameAccount) {
            $this->error('该产品未关联有效的游戏账号');
        }
        try {
            $client = new G2gClient($product->gameAccount);
            $client->updatePrice($product->product_id, $price, $product->id);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());
        }
        $product->price = $price;
        $product->save();
        $this->success('改价成功', ['price' => $price]);
    }

    public function select(): void
    {
        $this->success('', [
            'list' => $this->tableList(Model::class, [], ['title', 'product_id'])
                ->field('title as label,id as value')
                ->limit(20)
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
            ['v' => 'status', 'label' => '状态', 'render' => 'status', 'sort' => 'status'],
            ['v' => 'created_at', 'label' => '创建时间', 'search' => 'created_at', 'searchType' => 'daterange', 'sort' => 'created_at'],
        ];
    }
}
