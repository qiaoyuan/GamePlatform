<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\annotation\Permission;
use app\common\model\GameProduct;
use app\common\model\GameAccount;
use app\common\model\CrawlData;
use app\common\model\PriceStrategy as Model;
use app\common\model\PriceStrategyProduct;
use app\common\service\GameProductPriceService;
use app\common\service\GameProductStockService;
use app\common\service\PriceStrategyService;
use app\common\service\PriceStrategyListService;
use think\facade\Db;

/**
 * 改价策略模板
 */
class PriceStrategy extends BaseController
{
    /**
     * 列表字段定义
     */
    public function columns(): array
    {
        return [
            ['v' => 'id',   'label' => 'ID',       'width' => 80,  'searchType' => 'number', 'sort' => 'id'],
            ['v' => 'name', 'label' => '策略名称', 'width' => 160, 'searchType' => 'like'],
            [
                'v'          => 'target_name',
                'label'      => '对标竞品池',
                'width'      => 150,
                'search'     => 'crawl_target_id',
                'searchType' => 'multiple',
                'searchList' => '/crawl/select',
                'sort'       => 'crawl_target_id',
            ],
            ['v' => 'products_count', 'label' => '绑定产品数', 'width' => 100, 'search' => false],
            ['v' => 'filter_price', 'label' => '最低价', 'width' => 110, 'search' => false],
            ['v' => 'last_change_price', 'label' => '上次改价价格', 'width' => 130, 'search' => false],
            ['v' => 'msg', 'label' => 'msg（竞品最低价 / 店铺）', 'width' => 260, 'search' => false,
                'headerTooltip' => "最新一轮竞品中，满足策略店铺黑白名单、库存、好评率、币种条件的最低价及店铺。\n白名单沿用策略规则，豁免库存/好评率；黑名单优先。\n价格门槛仅用于此列标红：低于策略最低价标红，等于不标红。实际改价规则不变。"],
            [
                'v'          => 'auto_run',
                'label'      => '爬后自动执行',
                'width'      => 110,
                'render'     => 'boolean',
                'searchType' => 'multiple',
                'searchList' => [['label' => '是', 'value' => 1], ['label' => '否', 'value' => 0]],
                'search'     => 'auto_run',
            ],
            ['v' => 'interval_minutes', 'label' => '改价频率(分钟)', 'width' => 110, 'search' => false],
            [
                'v'          => 'status',
                'label'      => '状态',
                'width'      => 90,
                'render'     => 'status',
                'search'     => 'status',
                'searchType' => 'multiple',
                'searchList' => Model::getStatusList(),
                'sort'       => 'status',
            ],
            ['v' => 'last_run_at', 'label' => '最后执行时间', 'width' => 160, 'search' => 'last_run_at', 'searchType' => 'daterange', 'sort' => 'last_run_at'],
            ['v' => 'created_at',  'label' => '创建时间',     'width' => 160, 'search' => 'created_at',  'searchType' => 'daterange', 'sort' => 'created_at'],
        ];
    }

    /**
     * 列表
     */
    #[Permission(title: '改价策略', isMenu: 1, parentUrl: 'gameProduct/index', isHideSub: 1)]
    public function index(): void
    {
        $lists = $this->scopeOwnedData($this->tableList(Model::class, PriceStrategyListService::ORDER, ['name']), 'price_strategy')
            ->with(['crawlTarget'])
            ->withCount(['products'])
            ->selectData();
        if (!is_numeric($lists)) {
            $listService = new PriceStrategyListService();
            $prices = $listService->latestPrices(
                $this->scopeOwnedData(Db::table('price_strategy_log'), 'price_strategy_log'),
                $lists->column('id')
            );
            $messages = $listService->competitorMessages(
                $lists,
                $this->scopeOwnedData(CrawlData::where('price', '>', 0), 'crawl_data'),
                $this->scopeOwnedData(Db::table('game_product'), 'game_product')
            );
            $lists->each(function (Model $item) use ($prices, $messages) {
                $item->target_name = $item->crawlTarget ? $item->crawlTarget->name : '--';
                $item->filter_price = $this->getConfigPrice($item->config);
                $price = $prices[$item->id] ?? null;
                $item->last_change_price = $price === null ? '—' :
                    (str_contains((string) $price, '.') ? rtrim(rtrim((string) $price, '0'), '.') : (string) $price);
                $item->msg = $messages[$item->id]['msg'] ?? '暂无符合条件的竞品';
                $item->msg_color = $messages[$item->id]['msg_color'] ?? '';
            });
        }
        $this->success('', [
            'list' => $lists,
        ]);
    }

    #[Permission(title: '拖拽排序')]
    public function sort(): void
    {
        try {
            (new PriceStrategyListService())->reorder(
                $this->scopeOwnedData(Db::table('price_strategy'), 'price_strategy'),
                $this->request->post('before_ids/a', []),
                $this->request->post('after_ids/a', [])
            );
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage());
        }
        $this->success('排序已保存');
    }

    /**
     * 从维度 JSON 中读取最低价(filter_price)，兼容旧的 price/minimum_price/floor_price 配置。
     */
    private function getConfigPrice($config): ?float
    {
        if (is_string($config)) {
            $config = json_decode($config, true);
        }
        if (!is_array($config)) {
            return null;
        }
        $dimensions = $config['dimensions'] ?? [];
        if (is_string($dimensions)) {
            $dimensions = json_decode($dimensions, true);
        }
        $dimension = is_array($dimensions) && isset($dimensions[0]) ? $dimensions[0] : $config;
        if (is_string($dimension)) {
            $dimension = json_decode($dimension, true);
        }
        if (!is_array($dimension)) {
            return null;
        }
        $value = $dimension['filter_price']
            ?? $dimension['price']
            ?? $dimension['minimum_price']
            ?? $dimension['floor_price']
            ?? null;
        if ($value === null || $value === '' || !is_numeric($value)) {
            return null;
        }
        $price = (float) $value;
        return is_finite($price) && $price >= 0 ? $price : null;
    }

    /**
     * 只更新维度 JSON 中的最低价(filter_price)，保留其它策略配置。
     */
    private function setConfigPrice($config, ?float $price): array
    {
        if (is_string($config)) {
            $config = json_decode($config, true);
        }
        $config = is_array($config) ? $config : [];
        $dimensions = $config['dimensions'] ?? [];
        if (is_string($dimensions)) {
            $dimensions = json_decode($dimensions, true);
        }
        if (!is_array($dimensions) || !isset($dimensions[0])) {
            $dimension = $config;
            unset($dimension['dimensions']);
            $dimensions = [$dimension];
        }
        $dimension = $dimensions[0];
        if (is_string($dimension)) {
            $dimension = json_decode($dimension, true);
        }
        $dimension = is_array($dimension) ? $dimension : [];
        $dimension['filter_price'] = $price;
        unset($dimension['price'], $dimension['minimum_price'], $dimension['floor_price']);
        $dimensions[0] = $dimension;
        $config['dimensions'] = $dimensions;
        unset($config['filter_price'], $config['price'], $config['minimum_price'], $config['floor_price']);
        return $config;
    }

    /**
     * 详情（含维度配置，供编辑弹窗回填）
     */
    public function get(): void
    {
        $row = $this->scopeOwnedData(Model::where([]), 'price_strategy')->find(input('id'));
        $row ? $this->success('', ['info' => $row]) : $this->success('暂无数据');
    }

    /**
     * 新增
     */
    #[Permission(title: '添加策略')]
    public function add(): void
    {
        $this->assertOwnedData('crawl_target', input('crawl_target_id'), '请选择当前账号名下的竞品池');
        $this->mAdd(Model::class, ['except' => ['sort']]);
    }

    /**
     * 编辑
     */
    #[Permission(title: '编辑策略')]
    public function edit(): void
    {
        $this->assertOwnedData('price_strategy', input('id'));
        $this->assertOwnedData('crawl_target', input('crawl_target_id'), '请选择当前账号名下的竞品池');
        $this->mEdit(Model::class, ['except' => ['sort']]);
    }

    /**
     * 批量更新最低价（仅修改 JSON 维度中的 filter_price 字段）。
     */
    #[Permission(title: '批量更新价格')]
    public function batchPrice(): void
    {
        $ids = input('ids', []);
        $ids = is_array($ids) ? $ids : [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
        if (!$ids) {
            $this->error('请选择要更新的策略');
        }
        $this->assertOwnedData('price_strategy', $ids);

        $rawPrice = input('filter_price', null);
        if ($rawPrice === '' || $rawPrice === null) {
            $price = null;
        } elseif (!is_numeric($rawPrice) || !is_finite((float) $rawPrice) || (float) $rawPrice < 0) {
            $this->error('价格必须是大于等于 0 的数字');
        } else {
            $price = round((float) $rawPrice, 6);
        }

        $rows = Model::whereIn('id', $ids)->select();
        if (count($rows) !== count($ids)) {
            $this->error('部分策略不存在，请刷新列表后重试');
        }
        Db::transaction(function () use ($rows, $price) {
            foreach ($rows as $row) {
                $row->config = $this->setConfigPrice($row->config, $price);
                $row->save();
            }
        });

        $this->success('批量更新成功', ['count' => count($ids), 'filter_price' => $price]);
    }

    /**
     * 把策略绑定的全部产品修改为同一价格。
     * 未传 price 时默认使用该策略的最低价。
     */
    #[Permission(title: '批量修改产品价格')]
    public function batchProductPrice(): void
    {
        [$strategy, $products] = $this->getBoundProducts((int) input('id', 0));

        $rawPrice = input('price', null);
        if ($rawPrice === '' || $rawPrice === null) {
            $rawPrice = $this->getConfigPrice($strategy->config);
        }
        if (!is_numeric($rawPrice) || !is_finite((float) $rawPrice) || (float) $rawPrice <= 0) {
            $this->error('请填写大于 0 的价格，或先为策略设置最低价');
        }
        $price = round((float) $rawPrice, 6);

        $stat = ['total' => count($products), 'success' => 0, 'skip' => 0, 'fail' => 0, 'errors' => []];
        $productIndex = 0;
        foreach ($products as $product) {
            if ($productIndex > 0) {
                usleep(1_000_000);
            }
            $productIndex++;
            try {
                // 用户明确要求所有绑定产品都提交改价，
                // 即使本地价格相同也调用平台，可用于校正线上价格。
                GameProductPriceService::change($product, $price);
                $stat['success']++;
            } catch (\Throwable $e) {
                $stat['fail']++;
                $stat['errors'][] = $product->title . '：' . mb_substr($e->getMessage(), 0, 180);
            }
        }

        $this->success($this->formatBatchResult('批量改价', $stat), $stat + ['price' => $price]);
    }

    /**
     * 把策略绑定的全部产品修改为同一库存。
     * ELD 通过独立库存接口同步，成功后更新本地；其它平台只更新本地。
     */
    #[Permission(title: '批量修改产品库存')]
    public function batchProductStock(): void
    {
        [, $products] = $this->getBoundProducts((int) input('id', 0));

        // 弹窗初始化时复用同一权限接口，实时返回第一个绑定产品的当前库存。
        if ((int) input('preview', 0) === 1) {
            $product = $products->first();
            $this->success('', [
                'stock' => (int) $product->stock,
                'product_id' => (int) $product->id,
                'product_title' => $product->title,
            ]);
        }

        $rawStock = input('stock', null);
        if (filter_var($rawStock, FILTER_VALIDATE_INT) === false || (int) $rawStock <= 0) {
            $this->error('库存必须是大于 0 的整数');
        }
        $stock = (int) $rawStock;

        $stat = ['total' => count($products), 'success' => 0, 'skip' => 0, 'fail' => 0, 'errors' => []];
        foreach ($products as $product) {
            $offerData = $product->offer_data;
            $account = $product->gameAccount;
            $isEld = $account && (int) $account->platform === GameAccount::PLATFORM_ELDORADO;
            $hasOfferData = is_array($offerData) && !empty($offerData);
            $offerQuantity = $hasOfferData
                ? (int) ($offerData['details']['pricing']['quantity'] ?? 0)
                : null;

            if (!$isEld && (int) $product->stock === $stock
                && (!$hasOfferData || $offerQuantity === $stock)) {
                $stat['skip']++;
                continue;
            }
            try {
                if ($isEld) {
                    // 本地库存相同也提交，支持校正此前仅修改过本地库存的产品。
                    GameProductStockService::syncQuantity($product, $stock);
                    $stat['success']++;
                    continue;
                }
                $product->stock = $stock;
                if ($hasOfferData) {
                    $offerData['details']['pricing']['quantity'] = $stock;
                    $product->offer_data = $offerData;
                }
                $product->save();
                $stat['success']++;
            } catch (\Throwable $e) {
                $stat['fail']++;
                $stat['errors'][] = $product->title . '：' . mb_substr($e->getMessage(), 0, 180);
            }
        }

        $this->success($this->formatBatchResult('批量改库存', $stat), $stat + ['stock' => $stock]);
    }

    /**
     * @return array{0:Model,1:\think\model\Collection}
     */
    private function getBoundProducts(int $strategyId): array
    {
        if ($strategyId <= 0) {
            $this->error('策略参数不足');
        }
        $this->assertOwnedData('price_strategy', $strategyId);
        $strategy = Model::find($strategyId);
        if (!$strategy) {
            $this->error('策略不存在');
        }

        $productIds = PriceStrategyProduct::where('price_strategy_id', $strategyId)
            ->order('id', 'ASC')
            ->column('game_product_id');
        if (!$productIds) {
            $this->error('该策略尚未绑定产品');
        }
        $this->assertOwnedData('game_product', $productIds, '策略中存在无权操作的产品');

        $products = GameProduct::with(['gameAccount'])->whereIn('id', $productIds)->select();
        if (count($products) !== count($productIds)) {
            $this->error('部分绑定产品不存在，请重新绑定后再试');
        }
        return [$strategy, $products];
    }

    private function formatBatchResult(string $action, array $stat): string
    {
        $message = sprintf(
            '%s完成：成功 %d，跳过 %d，失败 %d',
            $action,
            $stat['success'],
            $stat['skip'],
            $stat['fail']
        );
        if ($stat['errors']) {
            $message .= '；' . $stat['errors'][0];
        }
        return $message;
    }

    /**
     * 删除（同时清理产品绑定）
     */
    #[Permission(title: '删除策略')]
    public function delete(): void
    {
        $this->assertOwnedData('price_strategy', $this->getInputPk());
        $this->mDelete(Model::class, [], function ($list) {
            $ids = is_object($list) ? $list->column('id') : (array) $list;
            if ($ids) {
                PriceStrategyProduct::whereIn('price_strategy_id', $ids)->delete();
            }
        });
    }

    /**
     * 修改状态
     */
    #[Permission(title: '修改状态')]
    public function status(): void
    {
        $this->assertOwnedData('price_strategy', $this->getInputPk());
        $status = input('status', 0);
        Model::update(['status' => $status], ['id' => $this->getInputPk()]);
        $this->success('修改成功', ['status' => $status]);
    }

    /**
     * 该策略已绑定的产品（供绑定弹窗回填选中项）
     */
    #[Permission(title: '查看绑定产品')]
    public function boundProducts(): void
    {
        $id  = (int) input('id', 0);
        $this->assertOwnedData('price_strategy', $id);
        $ids = PriceStrategyProduct::where('price_strategy_id', $id)->column('game_product_id');
        $list = $ids
            ? $this->scopeOwnedData(GameProduct::whereIn('id', $ids), 'game_product')
                ->field('id as value,title as label')->select()
            : [];
        $this->success('', ['list' => $list]);
    }

    /**
     * 绑定产品：用提交的产品集合覆盖该策略的绑定关系。
     * 因 game_product_id 唯一，选中的产品若已属于别的策略会被移动到当前策略。
     */
    #[Permission(title: '绑定产品')]
    public function bindProducts(): void
    {
        $id = (int) input('id', 0);
        $this->assertOwnedData('price_strategy', $id);
        if (!$id || !Model::find($id)) {
            $this->error('策略不存在');
        }
        $productIds = input('product_ids', []);
        $productIds = is_array($productIds) ? array_values(array_unique(array_filter(array_map('intval', $productIds)))) : [];
        if ($productIds) {
            $this->assertOwnedData('game_product', $productIds, '只能绑定当前账号名下的游戏产品');
        }

        Db::transaction(function () use ($id, $productIds) {
            // 清空该策略原有绑定
            PriceStrategyProduct::where('price_strategy_id', $id)->delete();
            if ($productIds) {
                // 把选中的产品从其它策略中解绑（保证 1 产品 1 策略）
                PriceStrategyProduct::whereIn('game_product_id', $productIds)->delete();
                $now  = date('Y-m-d H:i:s');
                $rows = array_map(fn ($pid) => [
                    'price_strategy_id' => $id,
                    'game_product_id'   => $pid,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ], $productIds);
                (new PriceStrategyProduct)->insertAll($rows);
            }
        });

        $this->success('绑定成功', ['count' => count($productIds)]);
    }

    /**
     * 手动执行策略
     */
    #[Permission(title: '执行策略')]
    public function execute(): void
    {
        $id       = (int) input('id', 0);
        $this->assertOwnedData('price_strategy', $id);
        $strategy = Model::find($id);
        if (!$strategy) {
            $this->error('策略不存在');
        }
        if ($strategy->status != Model::STATUS_ON) {
            $this->error('策略已停用，无法执行');
        }
        // 注意：$this->success()/error() 是通过抛 HttpResponseException 返回响应的，
        // 不能放在 catch(\Throwable) 的 try 内，否则会被误当成异常吞掉。故只包住 runStrategy。
        try {
            $stat = (new PriceStrategyService)->runStrategy($strategy);
        } catch (\Throwable $e) {
            $this->systemError('执行失败: ' . $e->getMessage());
            return;
        }
        $this->success("执行完成：成功 {$stat['success']}，跳过 {$stat['skip']}，失败 {$stat['fail']}", $stat);
    }
}
