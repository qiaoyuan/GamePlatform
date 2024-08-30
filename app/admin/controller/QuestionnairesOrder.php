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
        $lists = $this->tableList(Model::class, ['created_at' => 'DESC'])->with(['questionnaire','user'])
            ->selectData();

        if(!is_numeric($lists)) {
            $lists->each(function (Model $item)  {
                $payMap = Model::$PAY_MAP;
                $statusMap = Model::$statusMap;
                $item->pay_status_name = $payMap[$item->pay_status];
                $item->status_name = $statusMap[$item->status];
                $item->channel_id = 0;
                if(!empty($item->user)) {
                   $item->channel_id = $item->user->channel_id;
                }
            });
        }


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
            [
                'v' => 'questionnaire.title',
                'label' => '问卷名称',
                'sort' => 'questionnaire_id',
                'search' => 'questionnaire_id',
                'searchType' => 'multiple',
                'searchList' => '/questionnaires/select',
            ],
            [
                'v' => 'order_id',
                'label' => '订单号',
                'sort' => 'order_id',
            ],
            ['v' => 'uid', 'label' => '微信用户ID', 'searchType' => 'number', 'sort' => 'uid'],
            ['v' => 'channel_id', 'label' => '渠道ID', 'searchType' => 'number', 'sort' => 'channel_id'],
            ['v' => 'created_at', 'label' => '创建时间', 'searchType' => 'number', 'sort' => 'created_at'],
            ['v' => 'price', 'label' => '订单价格', 'searchType' => 'number', 'sort' => 'price'],
            ['v' => 'status_name', 'search'=>'status', 'label' => '订单状态', 'searchType' => 'multiple', 'searchList' => Model::getStatusList()],
            ['v' => 'pay_status_name', 'search'=>'pay_status', 'label' => '支付状态', 'searchType' => 'multiple', 'searchList' => Model::getPayStatusList()],
        ];
    }
}
