<?php

namespace app\common\model;

use think\helper\Str;

/**
 * @property int $id 权限ID
 * @property string $title
 * @property int $parent_id 上级权限ID
 * @property int $sort 排序（同级有效）
 * @property string $url
 * @property int $is_hide
 * @property int $is_menu 是否是菜单
 * @property string $icon
 * @property int $is_hide_sub
 * @property int $level
 * @property int $status
 */
class AdminPermission extends Base
{

    protected $autoWriteTimestamp = false;
    protected $table = 'admin_permission';
    protected $field = [
        'id',
        'title',
        'parent_id',
        'sort',
        'url',
        'is_hide',
        'is_menu',
        'icon',
        'is_hide_sub',
        'level',
        'status',
    ];
    protected $pk = 'id';

    protected $type = ['id' => 'integer', 'is_menu' => 'integer'];

    public function parent()
    {
        return $this->hasOne('AdminPermission', 'id', 'parent_id');
    }

    public function roles()
    {
        return $this->belongsToMany(AdminRole::class, AdminRolePermission::class, 'admin_role_id', 'admin_permission_id');
    }

    /**
     * @param mixed $id
     */
    public static function remove($id)
    {
        is_array($id) || $id = [$id];
        (new AdminPermission())->where('id', 'IN', $id)->delete();
        (new AdminRolePermission())->where('admin_permission_id', 'IN', $id)->delete();
        $sub_ids = AdminPermission::where('parent_id', 'IN', $id)->column('id');
        if ($sub_ids) {
            self::remove($sub_ids);
        }
    }

    /**
     * @param $adminId
     * @return array
     */
    public static function getMenuByAdmin(int $adminId): array
    {
        $cacheKey = 'admin_menu_' . $adminId;
        return fastCache($cacheKey, function () use ($adminId) {
            $menus = [];
            if ($adminId == 1) {
                $menus = (new AdminPermission())->where('is_menu', 1)->order('sort', Base::SORT_FIELD_TYPE)->select()->toArray();
            } else {
                //按角色查询权限
                $roleIds = (new AdminRoleAdmin())->where('admin_id', $adminId)->column('admin_role_id');
                if ($roleIds) {
                    $roleIds = AdminRole::where('id', 'IN', $roleIds)->where('status', 1)->column('id');
                    if (!$roleIds) {
                        return $menus;
                    }
                    $permission_ids = (new AdminRolePermission())->where('admin_role_id', 'IN', $roleIds)->column('admin_permission_id');
                    if ($permission_ids) {
                        $menus = (new AdminPermission())->where('id', 'IN', $permission_ids)
                            ->where('is_menu', 1)
                            ->order('sort', 'DESC')
                            ->select()
                            ->toArray();
                    }
                }
            }
            $menus = toTree($menus);
            foreach ($menus as &$first) {
                if (isset($first['children']) && $first['parent_id'] == 0) {
                    if ($first['is_hide_sub']) {
                        $menus = array_merge($menus, $first['children']);
                        unset($first['children']);
                    } else {
                        foreach ($first['children'] as &$second) {
                            if (isset($second['children'])) {
                                if ($second['is_hide_sub']) {
                                    $first['children'] = array_merge($first['children'], $second['children']);
                                    unset($second['children']);
                                } else {
                                    foreach ($second['children'] as &$third) {
                                        if (isset($third['children'])) {
                                            if ($third['is_hide_sub']) {
                                                $second['children'] = array_merge($second['children'], $third['children']);
                                                unset($third['children']);
                                            } else {
                                                foreach ($third['children'] as $fourth) {
                                                    if ($fourth['is_hide_sub'] && isset($fourth['children'])) {
                                                        $third['children'] = array_merge($third['children'], $fourth['children']);
                                                        unset($fourth['children']);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            return $menus;
        }, null, 'admin_menu');
    }

    /**
     * @param int $adminId
     * @param string $url
     * @return bool
     */
    public static function checkPermission(int $adminId, string $url): bool
    {
        if ($adminId === 1) {
            return true;
        }
        $allPermissions = self::getAllPermissions();
        $url = Str::snake($url);
        if (isset($allPermissions[$url])) {
            if (!in_array($adminId, $allPermissions[$url])) {
                return false;
            }
        }
        return true;
    }

    /**
     * 批量修改level
     * @param array $pid 需要修改的parent_id
     * @param int $level
     */
    public static function updateLevel(array $pid, int $level)
    {
        self::update(['level' => $level], [['parent_id', 'IN', $pid]]);
        $sub = self::where('parent_id', 'IN', $pid)->column('id');
        if ($sub) {
            self::updateLevel($sub, $level + 1);
        }
    }

    public static function getPermissionByAdmin(int $adminId): array
    {
        return array_keys(array_filter(self::getAllPermissions(), function ($item) use ($adminId) {
            return is_array($item) && in_array($adminId, $item);
        }));
    }

    public static function getAllPermissions(): array
    {
        $cacheKey = 'admin_all_permission';
        return fastCache($cacheKey, function () {
            $allPermissions = [];
            $permissions = (new AdminPermission())->where('url', '<>', '')
                ->field('id,url')->with('roles.admins')->select();
            foreach ($permissions as $permission) {
                foreach ($permission['roles'] as $role) {
                    if (!$role['status']) {
                        continue;
                    }
                    foreach ($role['admins'] as $admin) {
                        if (isset($allPermissions[$permission['url']])) {
                            $allPermissions[$permission['url']][] = $admin['id'];
                        } else {
                            $allPermissions[$permission['url']] = [$admin['id']];
                        }
                    }
                }
            }
            return $allPermissions;
        }, null, 'admin_permission');
    }
}
