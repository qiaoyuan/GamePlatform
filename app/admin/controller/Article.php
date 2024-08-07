<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\enum\ArticleModule;
use app\common\model\Article as Model;
use app\common\annotation\Permission;

#[Permission(title: '内容', isMenu: 1, url: 'article')]
class Article extends BaseController
{
    #[Permission(parentUrl: 'article', title: '文章', isMenu: 1, sort: 1, isHideSub: 1)]
    public function index()
    {
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['title', 'desc'])
            ->where('module', $this->request->param('tab', ArticleModule::Article->value))
            ->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }

    #[Permission(title: '修改文章')]
    public function edit()
    {
        $this->mEdit(model: Model::class, with: ['articleContent']);
    }

    #[Permission(title: '添加文章', isMenu: 1, isHide: 1)]
    public function add()
    {
        $this->mAdd(
            Model::class,
            ['append' => ['admin_id' => $this->request->admin_id]],
            ['articleContent']
        );
    }

    #[Permission(title: '删除文章')]
    public function delete()
    {
        $this->mDelete(Model::class);
    }

    #[Permission(title: '彻底删除文章')]
    public function forceDelete()
    {
        $this->mForceDelete(Model::class, ['articleContent']);
    }

    #[Permission(title: '恢复文章')]
    public function restore()
    {
        parent::restore();
    }

    public function get()
    {
        $this->success('', [
            'detail' => Model::with(['articleContent'])->find(input('id'))
        ]);
    }

    #[Permission(title: '修改状态')]
    public function status()
    {
        $status = input('status', 0);
        Model::update(['status' => $status], ['id' => $this->getInputPk()]);
        $this->success('', ['status' => $status]);
    }

    public function select()
    {
        $this->success('', [
            'list' => $this->tableList(Model::class, ['id' => 'DESC'], ['title', 'desc'])
                ->field('title as label,id as value')
                ->select()
        ]);
    }

    public function tabs()
    {
        $this->success('', [
            'list' => mapToSelect(ArticleModule::getMap(), 'label', 'name')
        ]);
    }

    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id'],
            ['v' => 'title', 'label' => '标题'],
            [
                'v' => 'article_category_id',
                'label' => '分类',
                'sort' => 'article_category_id',
                'searchType' => 'multiple',
                'searchList' => '/articleCategory/select?module_match=' . $this->request->param('tab', ArticleModule::Article->value),
                'replace' => true,
            ],
            ['v' => 'thumb', 'label' => '缩略图', 'render' => 'image'],
            ['v' => 'status', 'label' => '状态', 'render' => 'status', 'sort' => 'status'],
            ['v' => 'created_at', 'label' => '日期', 'searchType' => 'daterange', 'sort' => 'created_at'],
            ['v' => 'desc', 'label' => '简介'],
            [
                'v' => 'admin_id',
                'label' => '添加人',
                'sort' => 'admin_id',
                'searchType' => 'multiple',
                'searchList' => '/admin/select',
                'replace' => true,
            ],
            ['v' => 'sort', 'label' => '排序', 'searchType' => 'number', 'sort' => 'sort'],
            ['v' => 'is_index', 'label' => '是否首页推荐', 'render' => 'boolean', 'sort' => 'is_index'],
        ];
    }
}
