<?php

namespace app\common\model;

/**
 * @property int $id
 * @property string $title 角色名称
 * @property string $description 角色描述
 * @property int $status 状态
 * @property string $created_at
 * @property string $updated_at
 */
class AdminRole extends Base
{
    protected $table = 'admin_role';
    protected $field = [
        'id',
        'title',
        'description',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $pk = 'id';

    protected $type = ['id' => 'integer'];

    public function permissions()
    {
        return $this->belongsToMany(AdminPermission::class, AdminRolePermission::class, 'admin_permission_id', 'admin_role_id');
    }

    public function admins()
    {
        return $this->belongsToMany(Admin::class, AdminRoleAdmin::class, 'admin_id', 'admin_role_id');
    }

    /**
     * @param mixed $id
     */
    public static function remove($id)
    {
        is_array($id) || $id = [$id];
        (new AdminRole())->where('id', 'IN', $id)->delete();
        (new AdminRolePermission())->where('admin_role_id', 'IN', $id)->delete();
        (new AdminRoleAdmin())->where('admin_role_id', 'IN', $id)->delete();
    }
}
