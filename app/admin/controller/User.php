<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\User as Model;
use think\helper\Arr;

class User extends BaseController
{
    /**
     * @permission_parent_url user
     * @permission_title 用户
     * @permission_is_menu
     * @permission_sort 1
     * @permission_is_hide_sub
     */
    public function index()
    {
        $lists = $this->tableList(
                Model::class,
                ['id' => 'DESC'],
                ['nickname', 'phone', 'info.id_card_no', 'info.wechat', 'info.alipay', 'info.real_name']
            )
            ->withJoin(['info'])
            ->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }

    /**
     * @permission_title 修改用户
     */
    public function edit()
    {
        $data = $this->validate('edit');
        if (isset($data['password']) && $data['password']) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        Model::update(Arr::only($data, [
            'nickname',
            'phone',
            'password',
            'avatar'
        ]), ['id' => $data['id']]);
        $this->success('修改成功');
    }

    /**
     * @permission_title 添加用户
     * @permission_is_menu
     * @permission_is_hide
     */
    public function add()
    {
        $this->mAdd(Model::class);
    }

    /**
     * @permission_title 删除用户
     */
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

    public function create()
    {
        $this->success('', [
            'frozenType' => [],
        ]);
    }

    /**
     * @permission_title 修改用户状态
     */
    public function status()
    {
        $status = input('status', 0);
        Model::update(['status' => $status], ['id' => $this->request->post('id')]);
        $this->success('修改成功', ['status' => $status]);
    }

    public function select()
    {
        $this->success('', [
            'list' => $this->tableList(Model::class, [], ['nickname', 'phone', 'id'])
                ->field('nickname as label,id as value')
                ->limit(20)
                ->select()
        ]);
    }

    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id'],
            ['v' => 'nickname', 'label' => '昵称'],
            ['v' => 'avatar', 'label' => '头像', 'render' => 'image', 'search' => false],
            ['v' => 'phone', 'label' => '电话'],
            ['v' => 'info.real_name', 'label' => '真实姓名'],
            ['v' => 'info.id_card_no', 'label' => '身份证'],
            ['v' => 'info.wechat', 'label' => '微信号'],
            ['v' => 'info.alipay', 'label' => '支付宝'],
            ['v' => 'point', 'label' => '积分', 'searchType' => 'number', 'sort' => 'point'],
            ['v' => 'total_point', 'label' => '累计获得积分', 'searchType' => 'number', 'sort' => 'total_point'],
            ['v' => 'amount', 'label' => '余额', 'searchType' => 'number', 'sort' => 'amount'],
            ['v' => 'frozen_amount', 'label' => '冻结余额', 'searchType' => 'number', 'sort' => 'frozen_amount'],
            [
                'v' => 'frozen_type',
                'label' => '冻结类型',
                'searchType' => 'multiple',
                'sort' => 'frozen_type',
                'searchList' => [ 'url' => '/user/create', 'key' => 'frozenType'],
                'replace' => true,
            ],
            [
                'v' => 'inviteBy.nickname',
                'label' => '邀请人',
                'sort' => 'invite_by_id',
                'searchType' => 'remote',
                'searchOption' => [
                    'remoteUrl' => '/user/select',
                    'key' => 'k'
                ],
            ],
            ['v' => 'is_id_card_verify', 'label' => '是否实名认证', 'render' => 'boolean', 'sort' => 'is_id_card_verify'],
        ];
    }
}
