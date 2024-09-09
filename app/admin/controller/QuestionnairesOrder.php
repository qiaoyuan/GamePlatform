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
        $param = $this->request->param();


        $where = [];
        $uids = [];
        if(!empty($param['channel_id_match'])) {
            $uids = \app\common\model\User::where('channel_id', $param['channel_id_match'])->column('id');
            if(empty($uids)) {
                $this->success('', ['list'=>[]]);
            }
            $where[] = ['uid','in', $uids];
        }

        if(!empty($param['channel_name_like'])) {
            $channelIds = \app\common\model\UserChannel::where('title','like', '%'.$param['channel_name_like'].'%')->column('id');
            if(empty($channelIds)) {
                $this->success('', ['list'=>[]]);
            }
            $uids = \app\common\model\User::whereIn('channel_id', $channelIds)->column('id');
            if(empty($uids)) {
                $this->success('', ['list'=>[]]);
            }
            $where[] = ['uid','in', $uids];
        }

        if(!empty($param['platform_multiple'])) {
            $uids = \app\common\model\User::whereIn('platform', $param['platform_multiple'])->column('id');
            if(empty($uids)) {
                $this->success('', ['list'=>[]]);
            }
            $where[] = ['uid','in',  $uids];
        }

        $lists = $this->tableList(Model::class, ['created_at' => 'DESC'])->where($where)->with(['questionnaire', 'user' => function ($query){
            return $query->with('channel');
        }])->selectData();


        if (!is_numeric($lists)) {
            $lists->each(function (Model $item) {
                $payMap = Model::$PAY_MAP;
                $statusMap = Model::$statusMap;
                $paytypeMap = Model::$PAY_TYPE_MAP;
                $platformMap = \app\common\model\User::$PLATFORM_MAP;
                $item->pay_status_name = $payMap[$item->pay_status];
                $item->status_name = $statusMap[$item->status];
                $item->pay_type_name = $paytypeMap[$item->pay_type];
                $item->channel_id = 0;
                $item->channel_name = "无";
                $item->platform_name = $platformMap[\app\common\model\User::WX_PLATFORM];
                if (!empty($item->user)) {
                    $item->channel_id = $item->user->channel_id;
                    $item->platform_name = $platformMap[$item->user->platform];
                    if(!empty($item->user->channel)) {
                        $item->channel_name = $item->user->channel->title;
                    }
                }

            });
        }

        $summaryRes = Model::where($where)->field("count(order_id) order_id, sum(price) price")->select();
        $summary = [];
        if(!empty($summaryRes)) {
            $summary = [
                'order_id' => $summaryRes[0]['order_id'],
                'price' => $summaryRes[0]['price']
            ];
        }

        $this->success('', [
            'list' => $lists,
            'summary' => $summary
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
            ['v' => 'uid', 'label' => '微信用户ID', 'searchType' => 'match', 'sort' => 'uid'],
            ['v' => 'platform_name', 'search'=>'platform', 'label' => '平台', 'searchType' => 'multiple', 'searchList' => \app\common\model\User::getPlatformList(), 'sort' => 'platform'],
            ['v' => 'pay_type_name','search'=>'pay_type', 'label' => '支付方式', 'searchType' => 'multiple', 'searchList' => Model::getPayTypeList(), 'sort' => 'pay_type'],
            ['v' => 'channel_id', 'label' => '渠道ID', 'searchType' => 'match', 'sort' => 'channel_id'],
            ['v' => 'channel_name', 'label' => '渠道名称', 'searchType' => 'like', 'sort' => 'channel_id'],
            ['v' => 'created_at', 'label' => '创建时间', 'searchType' => 'daterange', 'sort' => 'created_at'],
            ['v' => 'price', 'label' => '订单价格', 'searchType' => 'number', 'sort' => 'price'],
            ['v' => 'status_name', 'search' => 'status', 'label' => '订单状态', 'searchType' => 'multiple', 'searchList' => Model::getStatusList()],
            ['v' => 'pay_status_name', 'search' => 'pay_status', 'label' => '支付状态', 'searchType' => 'multiple', 'searchList' => Model::getPayStatusList()],
        ];
    }
}
