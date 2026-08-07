<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\annotation\Permission;
use app\common\model\GameAccount as GameAccountModel;

class GameAccount extends BaseController
{
    public function columns(): array
    {
        return [
            ['v' => 'id',                  'label' => 'ID',         'width' => 80,  'searchType' => 'number',    'sort' => 'id'],
            ['v' => 'user_id',             'label' => '用户ID',      'width' => 120, 'searchType' => 'like',      'sort' => 'user_id'],
            ['v' => 'account_name',        'label' => '账号名称',    'width' => 120, 'searchType' => 'like',      'sort' => 'account_name'],
            ['v' => 'platform',            'label' => '平台',        'width' => 80,  'searchType' => 'match',     'sort' => 'platform'],
            ['v' => 'active_device_token', 'label' => '设备令牌',    'width' => 200, 'hidden' => true],
            ['v' => 'long_lived_token',    'label' => '长期令牌',    'width' => 200, 'hidden' => true],
            ['v' => 'refresh_token',       'label' => '刷新令牌',    'width' => 200, 'hidden' => true],
            ['v' => 'status',              'label' => '状态',        'width' => 80,  'searchType' => 'match',     'sort' => 'status'],
            ['v' => 'created_at',          'label' => '创建时间',    'width' => 160, 'searchType' => 'daterange', 'sort' => 'created_at'],
            ['v' => 'updated_at',          'label' => '更新时间',    'width' => 160, 'sort' => 'updated_at'],
        ];
    }

    /**
     * 列表
     */
    #[Permission(title: '游戏账号', isMenu: 1, parentUrl: 'gameProduct/index', isHideSub: 1)]
    public function index(): void
    {
        $lists = $this->tableList(GameAccountModel::class, ['id' => 'DESC'])
            ->selectData();
        if (!is_numeric($lists)) {
            $lists->each(function (GameAccountModel $item) {
                $item->status_name = GameAccountModel::$STATUS_MAP[$item->status] ?? '';
            });
        }
        $this->success('', [
            'list' => $lists,
        ]);
    }

    /**
     * 下拉选项
     */
    #[Permission(title: '下拉选项')]
    public function select(): void
    {
        $this->success('', [
            'list' => GameAccountModel::field('account_name as label,id as value')->where('status', 1)->select(),
        ]);
    }

    /**
     * 平台枚举
     */
    #[Permission(title: '平台选项')]
    public function platform(): void
    {
        $this->success('', [
            'list' => GameAccountModel::getPlatformList(),
        ]);
    }

    /**
     * 详情
     */
    #[Permission(title: '查看详情')]
    public function get(): void
    {
        $row = GameAccountModel::find(input('id'));
        $row ? $this->success('', ['info' => $row]) : $this->success('暂无数据');
    }

    /**
     * 新增
     */
    #[Permission(title: '添加账号')]
    public function add(): void
    {
        $this->mAdd(GameAccountModel::class);
    }

    /**
     * 编辑
     */
    #[Permission(title: '编辑账号')]
    public function edit(): void
    {
        $this->mEdit(GameAccountModel::class);
    }

    /**
     * 删除
     */
    #[Permission(title: '删除账号')]
    public function delete(): void
    {
        $this->mDelete(GameAccountModel::class);
    }

    /**
     * 修改状态
     */
    #[Permission(title: '修改状态')]
    public function status(): void
    {
        $status = input('status', 0);
        GameAccountModel::update(['status' => $status], ['id' => input('id')]);
        $this->success('修改成功', ['status' => $status]);
    }
}
