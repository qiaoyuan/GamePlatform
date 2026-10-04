<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\annotation\Permission;
use app\common\model\CrawlData as Model;
use app\common\model\CrawlTarget;

/**
 * 竞品数据（爬取结果）展示
 *
 * 数据来源：Python 爬虫写入 crawl_data 表。本控制器仅做展示/清理，不提供新增编辑。
 */
class CompetitorProduct extends BaseController
{
    /**
     * 列表字段定义
     */
    public function columns(): array
    {
        return [
            ['v' => 'id',          'label' => 'ID',       'width' => 80,  'search' => 'id', 'searchType' => 'match', 'sort' => 'id'],
            [
                'v'          => 'target_name',
                'label'      => '爬取目标',
                'width'      => 140,
                'search'     => 'target_id',
                'searchType' => 'multiple',
                'searchList' => '/crawl/select',
                'sort'       => 'target_id',
            ],
            [
                'v'          => 'game_product_name',
                'label'      => '游戏产品',
                'width'      => 180,
                'search'     => 'game_product_id',
                'searchType' => 'multiple',
                'searchList' => '/gameProduct/select',
            ],
            ['v' => 'version',        'label' => '数据版本', 'width' => 90, 'search' => 'version', 'searchType' => 'match', 'sort' => 'version'],
            ['v' => 'seller_name',   'label' => '店铺',     'width' => 130, 'search' => 'seller_name', 'searchType' => 'like'],
            ['v' => 'product_title', 'label' => '产品标题', 'width' => 220, 'search' => 'product_title', 'searchType' => 'like'],
            ['v' => 'seller_level',  'label' => '卖家等级', 'width' => 90,  'search' => false],
            ['v' => 'is_online',     'label' => '在线',     'width' => 70,  'render' => 'boolean', 'search' => false],
            ['v' => 'stock',         'label' => '库存',     'width' => 90,  'search' => false],
            ['v' => 'price',         'label' => '单价',     'width' => 120, 'searchType' => 'number', 'sort' => 'price'],
            ['v' => 'rating',        'label' => '好评率',   'width' => 90,  'search' => 'rating', 'searchType' => 'match'],
            ['v' => 'currency',      'label' => '币种',     'width' => 70,  'search' => false],
            ['v' => 'offer_url',     'label' => '产品链接', 'width' => 200, 'render' => 'link', 'value' => 'offer_url', 'search' => false],
            ['v' => 'crawled_at',    'label' => '爬取时间', 'width' => 160, 'search' => 'crawled_at', 'searchType' => 'daterange', 'sort' => 'crawled_at'],
        ];
    }

    /**
     * 列表
     */
    #[Permission(title: '竞品数据', isMenu: 1, parentUrl: 'crawl/index', isHideSub: 1)]
    public function index(): void
    {
        // 默认按实际抓取时间倒序；价格仅在用户主动点选价格列时参与排序。
        $query = $this->scopeOwnedData(
            $this->tableList(Model::class, ['crawled_at' => 'DESC'], ['seller_name', 'product_title']),
            'crawl_data'
        )->field([
            'id', 'target_id', 'game_product_id', 'version', 'platform',
            'seller_id', 'seller_name', 'seller_level', 'seller_url', 'is_online',
            'product_title', 'offer_url', 'sold_count', 'sold_count_num',
            'stock', 'stock_num', 'price', 'currency', 'min_order',
            'delivery_time', 'rating', 'crawled_at', 'created_at',
        ])->with(['crawlTarget.gameProduct']);

        // game_product_id 属于 crawl_target，不属于 crawl_data，先转换为目标 ID 再筛选竞品。
        $productIds = input('game_product_id_multiple', []);
        if (is_string($productIds)) {
            $decoded = json_decode($productIds, true);
            $productIds = is_array($decoded) ? $decoded : explode(',', $productIds);
        }
        $productIds = array_values(array_unique(array_filter(
            array_map('intval', (array) $productIds),
            static fn (int $id): bool => $id > 0
        )));
        if ($productIds) {
            $targetQuery = CrawlTarget::whereIn('game_product_id', $productIds)
                ->whereNull('deleted_at');
            $targetIds = $this->scopeOwnedData($targetQuery, 'crawl_target')->column('id');
            $query->whereIn('target_id', $targetIds ?: [-1]);
        }

        // 查询缓存按最终 SQL 区分分页、筛选和数据权限条件，避免不同账号或条件串用结果。
        $query->cache(60);

        // 无筛选、无排序时按账号缓存精确总数 60 秒，避免每次刷新/翻页都扫描大表 COUNT。
        $canCacheTotal = !$this->hasExplicitListFilter() && !input('sort');
        if ($canCacheTotal) {
            $total = cache('competitor_product_total_' . (int) $this->request->admin_id);
            if (!is_numeric($total) || (int) $total < 0) {
                $total = (int) $query->count();
                cache('competitor_product_total_' . (int) $this->request->admin_id, $total, 60);
            }
            $lists = $query->paginate(max(1, (int) input('limit', 50)), (int) $total);
        } else {
            $lists = $query->selectData();
        }
        if (!is_numeric($lists)) {
            $lists->each(function (Model $item) {
                // 关联目标或游戏产品可能已被删除，回退显示 --
                $item->target_name = $item->crawlTarget ? $item->crawlTarget->name : '--';
                $item->game_product_name = $item->crawlTarget && $item->crawlTarget->gameProduct
                    ? $item->crawlTarget->gameProduct->title
                    : '--';
            });
        }
        $this->success('', [
            'list' => $lists,
        ]);
    }

    /** 当前请求是否包含用户主动设置的列表筛选条件。 */
    private function hasExplicitListFilter(): bool
    {
        if (input('k')) {
            return true;
        }
        foreach ($this->request->param() as $key => $value) {
            if ($value !== '' && $value !== null && !str_starts_with($key, '_') && str_contains($key, '_')) {
                return true;
            }
        }
        return false;
    }

    /**
     * 详情
     */
    public function get(): void
    {
        $row = $this->scopeOwnedData(Model::with(['crawlTarget.gameProduct']), 'crawl_data')->find(input('id'));
        if ($row) {
            $row->target_name = $row->crawlTarget ? $row->crawlTarget->name : '--';
            $row->game_product_name = $row->crawlTarget && $row->crawlTarget->gameProduct
                ? $row->crawlTarget->gameProduct->title
                : '--';
            $this->success('', ['info' => $row]);
        }
        $this->success('暂无数据');
    }

    /**
     * 删除
     */
    #[Permission(title: '删除竞品数据')]
    public function delete(): void
    {
        $this->assertOwnedData('crawl_data', $this->getInputPk());
        $this->mDelete(Model::class);
    }
}
