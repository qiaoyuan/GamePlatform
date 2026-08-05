<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\GameAccount as Model;
use app\common\annotation\Permission;

class GameAccount extends BaseController
{
    #[Permission(title: '平台游戏账号', isMenu: 1, parentUrl: 'user', isHideSub: 1)]
    public function index(): void
    {
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['user_id', 'account_name'])
            ->selectData();
        if (!is_numeric($lists)) {
            $lists->each(function (Model $item) {
                $item->platform_name = Model::$PLATFORM_MAP[$item->platform] ?? '';
                // 账号名称为空时列表展示 "--"，编辑表单(get())不受影响，仍取原始空值
                $item->account_name = $item->account_name !== '' ? $item->account_name : '--';
            });
        }
        $this->success('', [
            'list' => $lists,
        ]);
    }

    #[Permission(title: '添加游戏账号')]
    public function add(): void
    {
        $this->mAdd(Model::class);
    }

    #[Permission(title: '编辑游戏账号')]
    public function edit(): void
    {
        $this->mEdit(Model::class);
    }

    #[Permission(title: '删除游戏账号')]
    public function delete(): void
    {
        $this->mDelete(Model::class);
    }

    public function get(): void
    {
        $this->success('', [
            'detail' => Model::find(input('id')),
        ]);
    }

    #[Permission(title: '修改状态')]
    public function status(): void
    {
        $status = input('status', 0);
        Model::update(['status' => $status], ['id' => $this->getInputPk()]);
        $this->success('修改成功', ['status' => $status]);
    }

    public function select(): void
    {
        $list = $this->tableList(Model::class, [], ['user_id', 'account_name'])
            ->field('id as value,user_id,account_name')
            ->limit(20)
            ->select();
        $list = $list->map(function ($item) {
            return [
                'value' => $item['value'],
                'label' => $item['account_name'] !== '' ? $item['account_name'] : $item['user_id'],
            ];
        });
        $this->success('', [
            'list' => $list
        ]);
    }

    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id'],
            ['v' => 'user_id', 'label' => '用户ID'],
            ['v' => 'account_name', 'label' => '账号名称', 'search' => 'account_name', 'searchType' => 'like'],
            [
                'v' => 'platform_name',
                'search' => 'platform',
                'searchType' => 'multiple',
                'label' => '平台',
                'searchList' => Model::getPlatformList(),
                'sort' => 'platform',
            ],
            ['v' => 'active_device_token', 'label' => '设备活跃令牌', 'search' => false],
            ['v' => 'long_lived_token', 'label' => '长期访问令牌', 'search' => false],
            ['v' => 'refresh_token', 'label' => '刷新令牌', 'search' => false],
            ['v' => 'status', 'label' => '状态', 'render' => 'status', 'sort' => 'status'],
            ['v' => 'created_at', 'label' => '创建时间', 'search' => 'created_at', 'searchType' => 'daterange', 'sort' => 'created_at'],
        ];
    }
}
