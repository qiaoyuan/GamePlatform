<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\QuestionnairesOrder as Model;
use app\common\annotation\Permission;


class QuestionnairesOrder extends BaseController
{
    
    #[Permission(title: '订单列表', isMenu: 1, parentUrl: 'order', isHideSub: 1)]
    public function index(): void
    {
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['input_data', 'pay_extent'])
            ->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }

    #[Permission(title: '编辑订单')]
    public function edit(): void
    {
        $this->mEdit(Model::class);
    }

    #[Permission(title: '添加订单')]
    public function add(): void
    {
        $this->mAdd(Model::class);
    }

    #[Permission(title: '删除订单')]
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
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id'],
            [
                'v' => 'questionnaire',
                'label' => '问卷名称',
                'sort' => 'questionnaire_id',
                'searchType' => 'multiple',
                'searchList' => '/questionnaire/select',
                'replace' => true,
            ],
            [
                'v' => 'order_id',
                'label' => '订单号',
                'sort' => 'order_id',
                'searchType' => 'multiple',
                'searchList' => '/order/select',
                'replace' => true,
            ],
            ['v' => 'uid', 'label' => '用户', 'searchType' => 'number', 'sort' => 'uid'],
            ['v' => 'created_at', 'label' => '创建时间', 'searchType' => 'number', 'sort' => 'created_at'],
            ['v' => 'status', 'label' => '1：正常订单', 'render' => 'status', 'sort' => 'status'],
            ['v' => 'input_data', 'label' => '请求订单数据'],
            ['v' => 'price', 'label' => '问卷价格', 'searchType' => 'number', 'sort' => 'price'],
            ['v' => 'pay_status', 'label' => '0：未支付，1：支付', 'searchType' => 'number', 'sort' => 'pay_status'],
            ['v' => 'pay_extent', 'label' => '支付请求数据'],
        ];
    }
}
