<?php
namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\AdminPermission;
use app\common\model\AdminRole;
use app\common\model\AdminRolePermission;
use app\common\model\AdminRoleAdmin;
use think\facade\Cache;

class Role extends BaseController
{
    /**
     * @permission_parent_url system
     * @permission_title 角色管理
     * @permission_is_menu
     * @permission_sort 8
     */
    public function index()
    {
        $lists = $this->tableList(AdminRole::class, ['id' => 'ASC'])->where([['id', '>', 1]])->selectData();
        $this->success('', ['list' => $lists]);
    }

    /**
     * @permission_title 添加
     */
    public function add()
    {
        $this->mAdd('AdminRole');
    }

    /**
     * @permission_title 编辑
     */
    public function edit()
    {
        $this->mEdit('AdminRole');
    }

    /**
     * @permission_title 修改状态
     */
    public function status()
    {
        $status = input('status', 0);
        AdminRole::update(['status' => $status], ['id' => $this->request->post('id')]);
        Cache::tag('admin_menu')->clear();
        Cache::tag('admin_permission')->clear();
        $this->success('修改成功', ['status' => $status]);
    }

    /**
     * @permission_title 删除
     */
    public function delete()
    {
        $id = request()->post('id/a');
        $admin = (new AdminRoleAdmin())->where('admin_role_id', 'IN', $id)->value('admin_id');
        if ($admin) {
            $this->error('角色下有管理员,不能删除');
        }
        AdminRole::remove($id);
        $this->success();
    }

    public function admin()
    {
        $id = input('id');
        if ($id) {
            $role = AdminRole::find($id);
            if ($role) {
                $admins = AdminRoleAdmin::where('admin_role_id', $id)->column('admin_id');
                $role = $role->toArray();
                $role['admins'] = $admins;
                $this->success('', [
                    'role' => $role,
                    'admins' => \app\common\model\Admin::where('id', '>', 1)->field('id,username,nickname')->select()
                ]);
            }
        }
        $this->error('参数不足');
    }

    /**
     * @permission_title 修改角色成员
     */
    public function updateAdmin()
    {
        $id = input('id');
        $admin_ids = request()->post('admin_id/a');
        AdminRoleAdmin::where('admin_role_id', $id)->delete();
        $role = [];
        foreach ($admin_ids as $admin_id) {
            $role[] = ['admin_role_id' => $id, 'admin_id' => $admin_id];
        }
        $role && (new AdminRoleAdmin())->insertAll($role);
        Cache::tag('admin_menu')->clear();
        Cache::tag('admin_permission')->clear();
        $this->success();
    }

    public function permission()
    {
        $id = input('role_id');
        if ($id) {
            $role = AdminRole::with(['permissions' => function($query) {
            }])->find($id);
            if ($role) {
                $role = $role->toArray();
                $sort = array_column($role['permissions'], 'sort');
                array_multisort($sort, SORT_DESC, SORT_NUMERIC, $role['permissions']);
                $role['permissions'] = array_column($role['permissions'], 'id');
                $parent_id = AdminPermission::where('parent_id', 'IN', $role['permissions'])->column('parent_id');
                $parent_id = array_values(array_unique($parent_id));
                $role['permissions'] = array_values(array_diff($role['permissions'], $parent_id));
                $permissions = AdminPermission::order('sort', 'DESC')->select()->toArray();
                $permissions = toTree($permissions);
                $this->success('', ['role' => $role, 'permissions' => $permissions]);
            }
        }
        $this->error('参数不足');
    }

    /**
     * @permission_title 修改角色权限
     */
    public function updatePermission()
    {
        $id = input('role_id');
        $permissionIds = request()->post('admin_permission_id');
        $role = AdminRole::find($id);
        if ($role) {
            $permissionIds && $permissionIds = array_merge(
                $permissionIds,
                AdminPermission::where('id', 'IN', $permissionIds)->column('parent_id')
            );
            $permissionIds && $permissionIds = array_merge(
                $permissionIds,
                AdminPermission::where('id', 'IN', $permissionIds)->column('parent_id')
            );
            $permissionIds = array_values(array_unique($permissionIds));
            (new AdminRolePermission())->where('admin_role_id', $id)->delete();
            $new_p = [];
            foreach ($permissionIds as $permission_id) {
                if ($permission_id) {
                    $new_p[] = ['admin_role_id' => $id, 'admin_permission_id' => $permission_id];
                }
            }
            if ($new_p) {
                (new AdminRolePermission())->insertAll($new_p);
            }
            Cache::tag('admin_menu')->clear();
            Cache::tag('admin_permission')->clear();
            $this->success();
        }
        $this->error('参数错误');
    }

    public function columns()
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id', 'width' => 60],
            ['v' => 'title', 'label' => '名称'],
            ['v' => 'description', 'label' => '描述'],
            ['v' => 'status', 'label' => '状态', 'sort' => 'status', 'render' => 'status', 'width' => 60],
            ['v' => 'created_at', 'label' => '创建时间', 'sort' => 'created_at', 'width' => 160, 'searchType' => 'daterange'],
            ['v' => 'updated_at', 'label' => '更新时间', 'sort' => 'updated_at', 'width' => 160, 'searchType' => 'daterange'],
        ];
    }
}
