<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\AdminSetting as Model;
use think\helper\Str;

class AdminSetting extends BaseController
{
    /**
     * @permission_parent_url setting
     * @permission_title 通用配置
     * @permission_is_menu
     * @permission_sort 1
     * @permission_is_hide_sub
     */
    public function index()
    {
        $where = [];
        if ($this->request->controller() != 'AdminSetting') {
            $where[] = ['module', '=', Str::camel($this->request->controller())];
        }
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['module', 'title'])
            ->where($where)
            ->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }

    /**
     * @permission_title 修改通用配置
     */
    public function edit()
    {
        $this->mEdit(Model::class);
    }

    /**
     * @permission_title 添加通用配置
     * @permission_is_menu
     * @permission_is_hide
     */
    public function add()
    {
        $this->mAdd(Model::class, ['append' => ['module' => Str::camel($this->request->controller())]]);
    }

    /**
     * @permission_title 删除通用配置
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

    /**
     * @permission_title 修改通用配置状态
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
            'list' => $this->tableList(Model::class)->field('title as label,id as value')->select()
        ]);
    }

    public function tabs()
    {
        $this->success('', [
            'list' => mapToSelect(Model::$moduleMap, 'label', 'name')
        ]);
    }

    public function create()
    {
        $this->success('', [
            'list' => mapToSelect(Model::$moduleMap)
        ]);
    }

    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id'],
            ['v' => 'title', 'label' => '名称'],
            ['v' => 'sort', 'label' => '排序', 'searchType' => 'number', 'sort' => 'sort'],
            ['v' => 'created_at', 'label' => '日期', 'searchType' => 'daterange', 'sort' => 'created_at'],
            ['v' => 'status', 'label' => '状态', 'render' => 'status', 'sort' => 'status'],
        ];
    }
}
