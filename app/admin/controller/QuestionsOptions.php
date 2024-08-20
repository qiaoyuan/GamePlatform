<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\QuestionsOptions as Model;
use app\common\annotation\Permission;


class QuestionsOptions extends BaseController
{
    
    #[Permission(title: 'TODO: 名称', isMenu: 1, parentUrl: 'TODO: 上级路由', isHideSub: 1)]
    public function index(): void
    {
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['title'])
            ->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }

    #[Permission(title: '修改TODO: 名称')]
    public function edit(): void
    {
        $this->mEdit(Model::class);
    }

    #[Permission(title: '添加TODO: 名称')]
    public function add(): void
    {
        $this->mAdd(Model::class);
    }

    #[Permission(title: '删除TODO: 名称')]
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

    #[Permission(title: '修改TODO: 名称状态')]
    public function status(): void
    {
        $status = input('status', 0);
        Model::update(['status' => $status], ['id' => $this->getInputPk()]);
        $this->success('修改成功', ['status' => $status]);
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
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id'],
            [
                'v' => 'question_id',
                'label' => '问题id',
                'sort' => 'question_id',
                'searchType' => 'multiple',
                'searchList' => '/question/select',
                'replace' => true,
            ],
            ['v' => 'title', 'label' => '选项名称'],
            ['v' => 'created_at', 'label' => '日期', 'searchType' => 'number', 'sort' => 'created_at'],
            ['v' => 'score', 'label' => '分数', 'searchType' => 'number', 'sort' => 'score'],
            ['v' => 'sort', 'label' => '顺序', 'searchType' => 'number', 'sort' => 'sort'],
        ];
    }
}
