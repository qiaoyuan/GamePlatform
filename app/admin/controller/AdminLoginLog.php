<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\AdminLoginLog as Model;

class AdminLoginLog extends BaseController
{
    /**
     * @permission_parent_url system
     * @permission_title 登录日志
     * @permission_is_menu
     * @permission_sort 1
     * @permission_is_hide_sub
     */
    public function index()
    {
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['ip'])
            ->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }

    /**
     * @permission_title 删除登录日志
     */
    public function delete()
    {
        $this->mDelete(Model::class);
    }

    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id'],
            ['v' => 'ip', 'label' => 'IP'],
            [
                'v' => 'admin_id',
                'label' => '人员',
                'sort' => 'admin_id',
                'searchType' => 'multiple',
                'searchList' => '/admin/select',
                'replace' => true,
            ],
            ['v' => 'created_at', 'label' => '日期', 'searchType' => 'daterange', 'sort' => 'created_at'],
        ];
    }
}
