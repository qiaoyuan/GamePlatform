<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\annotation\Permission;
use app\common\model\CrawlTarget as CrawlTargetModel;
use app\common\model\GameProduct;
use app\common\service\CrawlService;

/**
 * 爬取目标管理
 */
class Crawl extends BaseController
{
    /**
     * 列表字段定义
     */
    public function columns(): array
    {
        return [
            ['v' => 'id',            'label' => 'ID',         'width' => 80,  'searchType' => 'number',    'sort' => 'id'],
            ['v' => 'name',           'label' => '任务名称',   'width' => 150, 'searchType' => 'like',      'sort' => 'name'],
            [
                'v'          => 'game_product_name',
                'label'      => '游戏产品',
                'width'      => 180,
                'search'     => 'game_product_id',
                'searchType' => 'multiple',
                'searchList' => '/gameProduct/select',
                'sort'       => 'game_product_id',
            ],
            ['v' => 'version',         'label' => '数据版本',   'width' => 90, 'search' => 'version', 'searchType' => 'match', 'sort' => 'version'],
            ['v' => 'url',           'label' => '目标链接',   'width' => 300, 'searchType' => 'like'],
            ['v' => 'category_name',  'label' => '产品分类',   'width' => 120, 'search' => 'category', 'searchType' => 'multiple', 'searchList' => CrawlTargetModel::getCategoryList(), 'sort' => 'category'],
            ['v' => 'status',          'label' => '状态',       'render' => 'status', 'sort' => 'status'],
            ['v' => 'last_crawl_at',   'label' => '最后爬取时间', 'width' => 160, 'searchType' => 'daterange', 'sort' => 'last_crawl_at'],
            ['v' => 'created_at',    'label' => '创建时间',   'width' => 160, 'searchType' => 'daterange', 'sort' => 'created_at'],
            ['v' => 'updated_at',    'label' => '更新时间',   'width' => 160, 'sort' => 'updated_at'],
        ];
    }

    /**
     * 列表
     */
    #[Permission(title: '竞品爬取', isMenu: 1, parentUrl: 'gameProduct/index', isHideSub: 1)]
    public function index(): void
    {
        $lists = $this->tableList(CrawlTargetModel::class, ['id' => 'DESC'])
            ->with(['gameProduct'])
            ->selectData();
        if (!is_numeric($lists)) {
            $lists->each(function (CrawlTargetModel $item) {
                $item->status_name = CrawlTargetModel::$STATUS_MAP[$item->status] ?? '';
                $item->category_name = CrawlTargetModel::$CATEGORY_MAP[$item->category] ?? $item->category;
                $item->game_product_name = $item->gameProduct ? $item->gameProduct->title : '--';
            });
        }
        $this->success('', [
            'list' => $lists,
        ]);
    }

    /**
     * 下拉选项
     */
    #[Permission(title: '下拉选项')]
    public function select(): void
    {
        $this->success('', [
            'list' => CrawlTargetModel::field('name as label,id as value')->where('status', 1)->select(),
        ]);
    }

    /**
     * 详情
     */
    #[Permission(title: '查看详情')]
    public function get(): void
    {
        $row = CrawlTargetModel::with(['gameProduct'])->find(input('id'));
        if ($row) {
            $row->game_product_name = $row->gameProduct ? $row->gameProduct->title : '--';
            $this->success('', ['info' => $row]);
        }
        $this->success('暂无数据');
    }

    /**
     * 新增
     */
    #[Permission(title: '添加目标')]
    public function add(): void
    {
        $this->ensureGameProduct();
        $this->mAdd(CrawlTargetModel::class);
    }

    /**
     * 编辑
     */
    #[Permission(title: '编辑目标')]
    public function edit(): void
    {
        $targetId = (int) input('id', 0);
        $this->ensureGameProduct($targetId);
        // 版本由新增时初始化，编辑接口不允许覆盖。
        $this->mEdit(CrawlTargetModel::class, ['except' => ['version']]);
    }

    /**
     * 删除
     */
    #[Permission(title: '删除目标')]
    public function delete(): void
    {
        $this->mDelete(CrawlTargetModel::class);
    }

    /**
     * 修改状态
     */
    #[Permission(title: '修改状态')]
    public function status(): void
    {
        $status = input('status', 0);
        CrawlTargetModel::update(['status' => $status], ['id' => input('id')]);
        $this->success('修改成功', ['status' => $status]);
    }

    /**
     * 校验爬虫目标绑定的游戏产品有效，且未被其他未删除目标占用。
     */
    private function ensureGameProduct(int $excludeTargetId = 0): void
    {
        $gameProductId = (int) input('game_product_id', 0);
        if ($gameProductId <= 0 || !GameProduct::where('id', $gameProductId)->find()) {
            $this->error('请选择有效的游戏产品');
        }

        $query = CrawlTargetModel::where('game_product_id', $gameProductId)
            ->whereNull('deleted_at');
        if ($excludeTargetId > 0) {
            $query->where('id', '<>', $excludeTargetId);
        }
        $occupied = $query->field('id,name')->find();
        if ($occupied) {
            $this->error(sprintf(
                '游戏产品已绑定爬虫目标「%s」(ID:%d)，一个游戏产品只能绑定一个爬虫目标',
                $occupied->name,
                $occupied->id
            ));
        }
    }

    /**
     * 执行爬取
     */
    #[Permission(title: '执行爬取')]
    public function crawl(): void
    {
        $id = input('id', 0);
        if (empty($id)) {
            $this->systemError('缺少目标ID');
        }

        try {
            $service = new CrawlService;
            $result  = $service->crawl((int) $id);

            $this->success("爬取完成，共 {$result['count']} 条", [
                'target'   => $result['target'],
                'products' => $result['products'],
                'count'    => $result['count'],
                'elapsed'  => $result['elapsed'] . 's',
            ]);
        } catch (\Throwable $e) {
            $this->systemError('爬取失败: ' . $e->getMessage());
        }
    }
}
