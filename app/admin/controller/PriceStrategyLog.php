<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\annotation\Permission;
use app\common\model\PriceStrategy;
use app\common\model\PriceStrategyLog as Model;

/**
 * 改价策略执行日志（只读）
 */
class PriceStrategyLog extends BaseController
{
    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'width' => 80, 'searchType' => 'number', 'sort' => 'id'],
            [
                'v'          => 'strategy_name',
                'label'      => '策略',
                'width'      => 150,
                'search'     => 'price_strategy_id',
                'searchType' => 'multiple',
                'searchList' => PriceStrategy::whereNull('deleted_at')
                    ->field('name as label,id as value')
                    ->select()
                    ->toArray(),
                'sort'       => 'price_strategy_id',
            ],
            [
                'v'          => 'product_title',
                'label'      => '产品',
                'width'      => 160,
                'search'     => 'game_product_id',
                'searchType' => 'multiple',
                'searchList' => '/gameProduct/select',
            ],
            ['v' => 'competitor_id',  'label' => '竞品ID',   'width' => 100, 'search' => 'competitor_id', 'searchType' => 'match', 'sort' => 'competitor_id'],
            ['v' => 'old_price',      'label' => '改价前',   'width' => 100, 'search' => false],
            ['v' => 'new_price',     'label' => '改价后',   'width' => 100, 'search' => false],
            ['v' => 'ref_price',     'label' => '参考价(最低)', 'width' => 110, 'search' => false],
            [
                'v'          => 'status',
                'label'      => '结果',
                'width'      => 90,
                'search'     => 'status',
                'searchType' => 'multiple',
                'searchList' => Model::getStatusList(),
                'replace'    => true,
            ],
            ['v' => 'message',    'label' => '说明',     'width' => 260, 'search' => false],
            ['v' => 'created_at', 'label' => '执行时间', 'width' => 160, 'search' => 'created_at', 'searchType' => 'daterange', 'sort' => 'created_at'],
        ];
    }

    #[Permission(title: '改价日志', isMenu: 1, parentUrl: 'priceStrategy/index', isHideSub: 1)]
    public function index(): void
    {
        $lists = $this->tableList(Model::class, ['id' => 'DESC'])
            ->with(['strategy', 'gameProduct'])
            ->selectData();
        if (!is_numeric($lists)) {
            $lists->each(function (Model $item) {
                $item->strategy_name = $item->strategy ? $item->strategy->name : '--';
                $item->product_title = $item->gameProduct ? $item->gameProduct->title : '--';
            });
        }
        $this->success('', [
            'list' => $lists,
        ]);
    }

    #[Permission(title: '删除日志')]
    public function delete(): void
    {
        $this->mDelete(Model::class);
    }
}
