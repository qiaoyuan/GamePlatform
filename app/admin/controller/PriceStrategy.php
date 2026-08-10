<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\annotation\Permission;
use app\common\model\GameProduct;
use app\common\model\PriceStrategy as Model;
use app\common\model\PriceStrategyProduct;
use app\common\service\PriceStrategyService;
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
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['name'])
            ->with(['crawlTarget'])
            ->withCount(['products'])
            ->selectData();
        if (!is_numeric($lists)) {
            $lists->each(function (Model $item) {
                $item->target_name = $item->crawlTarget ? $item->crawlTarget->name : '--';
            });
        }
        $this->success('', [
            'list' => $lists,
        ]);
    }

    /**
     * 详情（含维度配置，供编辑弹窗回填）
     */
    public function get(): void
    {
        $row = Model::find(input('id'));
        $row ? $this->success('', ['info' => $row]) : $this->success('暂无数据');
    }

    /**
     * 新增
     */
    #[Permission(title: '添加策略')]
    public function add(): void
    {
        $this->mAdd(Model::class);
    }

    /**
     * 编辑
     */
    #[Permission(title: '编辑策略')]
    public function edit(): void
    {
        $this->mEdit(Model::class);
    }

    /**
     * 删除（同时清理产品绑定）
     */
    #[Permission(title: '删除策略')]
    public function delete(): void
    {
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
        $ids = PriceStrategyProduct::where('price_strategy_id', $id)->column('game_product_id');
        $list = $ids
            ? GameProduct::whereIn('id', $ids)->field('id as value,title as label')->select()
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
        if (!$id || !Model::find($id)) {
            $this->error('策略不存在');
        }
        $productIds = input('product_ids', []);
        $productIds = is_array($productIds) ? array_values(array_unique(array_filter(array_map('intval', $productIds)))) : [];

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
