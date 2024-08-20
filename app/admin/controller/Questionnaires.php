<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\Questionnaires as Model;
use app\common\annotation\Permission;


class Questionnaires extends BaseController
{
    
    #[Permission(title: '问卷表', isMenu: 1, parentUrl: 'article', isHideSub: 1)]
    public function index(): void
    {
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['title', 'description'])->with(['articleCategorys'])
            ->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }

    #[Permission(title: '修改问卷')]
    public function edit(): void
    {
        $this->mEdit(Model::class);
    }

    #[Permission(title: '新增问卷')]
    public function add(): void
    {
        $this->mAdd(Model::class);
    }

    #[Permission(title: '删除问卷')]
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

    #[Permission(title: '修改问卷状态')]
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
            ['v' => 'title', 'label' => '问卷名称'],
            [
                'v' => 'articleCategorys.title',
                'label' => '问卷类型',
                'search' => 'article_category_id',
                'sort' => 'article_category_id',
                'searchType' => 'multiple',
                'searchList' => '/articleCategory/select',
            ],
            ['v' => 'description', 'label' => '问卷描述'],
            ['v' => 'easy', 'label' => '题目易懂'],
            ['v' => 'exact', 'label' => '结果准确性'],
            ['v' => 'utility', 'label' => '建议实用性'],
            ['v' => 'created_at', 'label' => '日期', 'search'=>'created_at','searchType' => 'date_range', 'sort' => 'created_at'],
        ];
    }
}
