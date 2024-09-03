<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\enum\ArticleModule;
use app\common\model\ArticleCategory as Model;
use app\common\annotation\Permission;

class ArticleCategory extends BaseController
{
    #[Permission(parentUrl: 'article', title: '文章分类', isMenu: 1, sort: 1, isHideSub: 1)]
    public function index()
    {
        $where = [['module', '=', $this->request->post('tab', ArticleModule::Article->value)]];
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['title'])
            ->where($where)
            ->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }

    #[Permission(title: '修改文章分类')]
    public function edit()
    {
        $this->mEdit(Model::class);
    }

    #[Permission(title: '添加文章分类')]
    public function add()
    {
        $this->mAdd(Model::class);
    }

    #[Permission(title: '删除文章分类')]
    public function delete()
    {
        $this->mDelete(Model::class);
    }

    public function get()
    {
        $this->success('', [
            'detail' => Model::find(input('id'))
        ]);
    }

    #[Permission(title: '修改状态')]
    public function status()
    {
        $status = input('status', 0);
        Model::update(['status' => $status], ['id' => $this->getInputPk()]);
        $this->success('修改成功', ['status' => $status]);
    }

    public function select()
    {
        $this->success('', [
            'list' => $this->tableList(Model::class)
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
            ['v' => 'icon_url', 'label' => 'icon', "render"=>"image"],
            ['v' => 'title', 'label' => '分类名称'],
            ['v' => 'description', 'label' => '简述'],
            ['v' => 'created_at', 'label' => '日期', 'searchType' => 'date', 'sort' => 'created_at'],
            ['v' => 'status', 'label' => '状态', 'render' => 'status', 'sort' => 'status'],
            ['v' => 'sort', 'label' => '排序', 'searchType' => 'number', 'sort' => 'sort'],
        ];
    }
}
