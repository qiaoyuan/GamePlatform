<?php
namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\AdminPermission;
use app\common\model\Base;
use think\facade\Cache;

class Permission extends BaseController
{
    /**
     * @permission_parent_url system
     * @permission_title 权限管理
     * @permission_is_menu
     * @permission_sort 7
     */
    public function index()
    {
        $where = [['parent_id', '=', $this->request->post('parent_id', 0)]];
        $lists = $this->tableList(AdminPermission::class, ['sort' => Base::SORT_FIELD_TYPE])
            ->where($where)
            ->select();
        $this->success('', ['list' => $lists]);
    }

    /**
     * @permission_title 添加
     */
    public function add()
    {
        $this->mAdd('AdminPermission', [], [], function () {
            Cache::tag('admin_menu')->clear();
            Cache::tag('admin_permission')->clear();
        });
    }

    /**
     * @permission_title 编辑
     */
    public function edit()
    {
        $this->mEdit('AdminPermission', [], [], function ($model) {
            Cache::tag('admin_menu')->clear();
            Cache::tag('admin_permission')->clear();
        });
    }

    /**
     * @permission_title 删除
     */
    public function delete()
    {
        $id = request()->post('id/a');
        // empty($id) && $this->mError('权限必须');
        $sub = (new AdminPermission())->where('parent_id', 'IN', $id)->value('id');
        if ($sub && $this->request->admin_id != 1) {
            $this->error('存在子权限，不能删除');
        }
        AdminPermission::remove($id);
        Cache::tag('admin_menu')->clear();
        Cache::tag('admin_permission')->clear();
        $this->success('删除成功');
    }

    public function select()
    {
        $this->success('', [
            'list' => $this->tableList(AdminPermission::class, ['sort' => 'DESC'])
                ->field('id as value,title as label, level')
                ->select()
        ]);
    }

    public function selectTree()
    {
        $this->success('', [
            'list' => toTree(AdminPermission::order('sort', 'DESC')
                ->field('id as value,title as label, level, parent_id')
                ->select()->toArray(), 'value')
        ]);
    }

    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match'],
            ['v' => 'title', 'label' => '名称'],
            ['v' => 'url', 'label' => 'URL'],
            ['v' => 'icon', 'label' => 'ICON', 'search' => false, 'render' => 'icon'],
            ['v' => 'is_menu', 'label' => '是否是菜单', 'render' => 'boolean'],
            ['v' => 'is_hide', 'label' => '是否隐藏', 'render' => 'boolean'],
            ['v' => 'is_hide_sub', 'label' => '是否隐藏子菜单', 'render' => 'boolean'],
            ['v' => 'sort', 'label' => '排序', 'searchType' => 'number'],
        ];
    }
}
