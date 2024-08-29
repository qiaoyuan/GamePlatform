<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\UserChannel as Model;
use app\common\annotation\Permission;


class UserChannel extends BaseController
{
    
    #[Permission(title: '渠道列表', isMenu: 1, parentUrl: 'article', isHideSub: 1)]
    public function index(): void
    {
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['title'])
            ->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }

    #[Permission(title: '修改渠道')]
    public function edit(): void
    {
        $this->mEdit(Model::class);
    }

    #[Permission(title: '添加渠道')]
    public function add(): void
    {
        $this->mAdd(Model::class);
    }

    #[Permission(title: '删除渠道')]
    public function delete(): void
    {
        $this->mDelete(Model::class);
    }

    public function get(): void
    {
        $this->success('', [
            'detail' => Model::find(input('id'))
        ]);
    }


    public function select(): void
    {
        $this->success('', [
            'list' => $this->tableList(Model::class)->field('title as label,id as value')->select()
        ]);
    }

    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => '渠道ID', 'searchType' => 'match', 'sort' => 'id'],
            ['v' => 'status', 'label' => '', 'render' => 'status', 'sort' => 'status'],
            ['v' => 'created_at', 'label' => '日期', 'searchType' => 'number', 'sort' => 'created_at'],
            ['v' => 'title', 'label' => ''],
        ];
    }
}
