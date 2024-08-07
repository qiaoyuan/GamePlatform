<?php

namespace app\admin\controller;

use app\admin\BaseController;

class AdminLog extends BaseController
{
    /**
     * @permission_parent_url system
     * @permission_title 操作日志
     * @permission_is_menu
     */
    public function index()
    {
        $lists = $this->tableList(\app\common\model\AdminLog::class, ['id' => 'DESC'])->with(['admin'])->selectData();
        $this->success('', ['list' => $lists]);
    }

    /**
     * @permission_title 删除操作日志
     */
    public function delete()
    {
        $id = request()->post('id/a');
        (new \app\common\model\AdminLog())->where('id', 'IN', $id)->delete();
        $this->success();
    }

    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'search' => 'id', 'searchType' => 'match', 'width' => '60'],
            ['v' => 'api', 'label' => '操作', 'width' => '120'],
            ['v' => 'admin.nickname', 'label' => '人', 'search' => 'admin_id', 'searchType' => 'multiple', 'searchList' => 'admin/select', 'width' => '80'],
            ['v' => 'created_at', 'label' => '时间', 'width' => '140', 'searchType' => 'daterange'],
            ['v' => 'param', 'label' => '参数', 'render' => 'html'],
        ];
    }
}
