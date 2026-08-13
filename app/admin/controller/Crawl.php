<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\annotation\Permission;
use app\common\model\CrawlTarget as CrawlTargetModel;
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
            ['v' => 'name',          'label' => '任务名称',   'width' => 150, 'searchType' => 'like',      'sort' => 'name'],
            ['v' => 'url',           'label' => '目标链接',   'width' => 300, 'searchType' => 'like'],
            ['v' => 'category_name',  'label' => '产品分类',   'width' => 120, 'search' => 'category', 'searchType' => 'multiple', 'searchList' => CrawlTargetModel::getCategoryList(), 'sort' => 'category'],
            ['v' => 'status', 'label' => '状态', 'render' => 'status', 'sort' => 'status'],            ['v' => 'last_crawl_at', 'label' => '最后爬取时间', 'width' => 160, 'searchType' => 'daterange', 'sort' => 'last_crawl_at'],
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
            ->selectData();
        if (!is_numeric($lists)) {
            $lists->each(function (CrawlTargetModel $item) {
                $item->status_name = CrawlTargetModel::$STATUS_MAP[$item->status] ?? '';
                $item->category_name = CrawlTargetModel::$CATEGORY_MAP[$item->category] ?? $item->category;
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
        $row = CrawlTargetModel::find(input('id'));
        $row ? $this->success('', ['info' => $row]) : $this->success('暂无数据');
    }

    /**
     * 新增
     */
    #[Permission(title: '添加目标')]
    public function add(): void
    {
        $this->mAdd(CrawlTargetModel::class);
    }

    /**
     * 编辑
     */
    #[Permission(title: '编辑目标')]
    public function edit(): void
    {
        $this->mEdit(CrawlTargetModel::class);
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
