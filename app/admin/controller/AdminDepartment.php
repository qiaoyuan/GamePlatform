<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\AdminDepartment as Model;

class AdminDepartment extends BaseController
{
    /**
     * @permission_parent_url system
     * @permission_title 部门
     * @permission_is_menu
     * @permission_sort 1
     * @permission_is_hide_sub
     */
    public function index()
    {
        $where = [['parent_id', '=', $this->request->post('parent_id', 0)]];
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['title', 'description'])
            ->where($where)
            ->with(['parent'])
            ->select();
        $this->success('', [
            'list' => $lists,
        ]);
    }

    /**
     * @permission_title 修改部门
     */
    public function edit()
    {
        $this->mEdit(Model::class);
    }

    /**
     * @permission_title 添加部门
     * @permission_is_menu
     * @permission_is_hide
     */
    public function add()
    {
        $this->mAdd(Model::class);
    }

    /**
     * @permission_title 删除部门
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
     * @permission_title 修改部门状态
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
            'list' => $this->tableList(Model::class)->field('title as label,id as value,level')->select()
        ]);
    }

    public function selectTree()
    {
        $this->success('', [
            'list' => toTree(
                $this->tableList(Model::class)
                    ->field('title as label,id as value,parent_id,level')
                    ->select()
                    ->toArray(),
                'value'
            )
        ]);
    }

    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id'],
            ['v' => 'title', 'label' => '名称'],
            ['v' => 'description', 'label' => '描述'],
            [
                'v' => 'parent.title',
                'label' => '上级部门',
                'sort' => 'parent_id',
                'search' => 'parent_id',
                'searchType' => 'multiple',
                'searchList' => '/adminDepartment/selectTree',
            ],
            [
                'v' => 'admin_id',
                'label' => '部门负责人',
                'sort' => 'admin_id',
                'searchType' => 'multiple',
                'searchList' => '/admin/select',
                'replace' => true,
            ],
            ['v' => 'created_at', 'label' => '日期', 'searchType' => 'daterange', 'sort' => 'created_at'],
        ];
    }
}
