<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\annotation\Permission;
use app\common\model\CrawlTarget as CrawlTargetModel;
use app\common\model\CompetitorProduct;
use app\common\service\CrawlService;
use think\response\Json;

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
            ['field' => 'id',          'name' => 'ID',            'width' => 80,   'searchType' => 'number'],
            ['field' => 'name',        'name' => '任务名称',      'width' => 150,  'searchType' => 'like'],
            ['field' => 'url',         'name' => '目标链接',      'width' => 300,  'searchType' => 'like'],
            ['field' => 'category',    'name' => '产品分类',      'width' => 120,  'searchType' => 'like'],
            ['field' => 'status',      'name' => '状态',          'width' => 80,   'searchType' => 'match'],
            ['field' => 'last_crawl_at', 'name' => '最后爬取时间','width' => 160,  'searchType' => 'daterange'],
            ['field' => 'created_at',  'name' => '创建时间',      'width' => 160],
            ['field' => 'updated_at',  'name' => '更新时间',      'width' => 160],
        ];
    }

    /**
     * 列表
     */
    #[Permission(title: '竞品爬取', isMenu: 1, parentUrl: 'gameProduct/index', isHideSub: 1)]
    public function index(): Json
    {
        $list = $this->tableList(new CrawlTargetModel)
            ->selectData();

        // 虚拟字段：状态翻译
        $list->each(function ($item) {
            $item['status_name'] = CrawlTargetModel::$STATUS_MAP[$item['status'] ?? 0] ?? '未知';
        });

        return $this->success([
            'list'  => $list,
            'count' => $this->tableList(new CrawlTargetModel)->count(),
        ]);
    }

    /**
     * 下拉选项
     */
    #[Permission(title: '下拉选项')]
    public function select(): Json
    {
        $list = CrawlTargetModel::field('id,name,category')->select();
        return $this->success(['list' => $list]);
    }

    /**
     * 详情
     */
    #[Permission(title: '查看详情')]
    public function get(): Json
    {
        $id  = $this->request->param('id', 0);
        $row = CrawlTargetModel::find($id);
        return $row ? $this->success(['info' => $row]) : $this->success([], '暂无数据');
    }

    /**
     * 新增
     */
    #[Permission(title: '添加目标')]
    public function add(): Json
    {
        return $this->mAdd(new CrawlTargetModel);
    }

    /**
     * 编辑
     */
    #[Permission(title: '编辑目标')]
    public function edit(): Json
    {
        return $this->mEdit(new CrawlTargetModel);
    }

    /**
     * 删除
     */
    #[Permission(title: '删除目标')]
    public function delete(): Json
    {
        return $this->mDelete(new CrawlTargetModel);
    }

    /**
     * 修改状态
     */
    #[Permission(title: '修改状态')]
    public function status(): Json
    {
        $id     = $this->request->param('id', 0);
        $status = $this->request->param('status', 0);
        CrawlTargetModel::where('id', $id)->update(['status' => $status]);
        return $this->success([], '操作成功');
    }

    /**
     * 执行爬取
     */
    #[Permission(title: '执行爬取')]
    public function crawl(): Json
    {
        $id = $this->request->param('id', 0);
        if (empty($id)) {
            return $this->systemError('缺少目标ID');
        }

        try {
            $service = new CrawlService;
            $result  = $service->crawl((int) $id);

            return $this->success([
                'target'   => $result['target'],
                'products' => $result['products'],
                'count'    => $result['count'],
                'elapsed'  => $result['elapsed'] . 's',
            ], "爬取完成，共 {$result['count']} 条");
        } catch (\Throwable $e) {
            return $this->systemError('爬取失败: ' . $e->getMessage());
        }
    }

    /**
     * 查看竞品结果
     */
    #[Permission(title: '查看竞品结果')]
    public function products(): Json
    {
        $targetId = $this->request->param('target_id', 0);
        $query    = CompetitorProduct::where('crawl_target_id', $targetId)
            ->order('price', 'asc');

        $list = $this->tableList($query, function ($query) {
            // 自定义搜索逻辑（如按店铺名搜索）
            $storeName = $this->request->param('store_name', '');
            if ($storeName) {
                $query->where('store_name', 'like', "%{$storeName}%");
            }
        })->selectData();

        return $this->success([
            'list'  => $list,
            'count' => $query->count(),
        ]);
    }
}
