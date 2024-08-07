<?php

namespace app\common\model;

use think\model\Pivot;

/**
 * @property int $admin_role_id
 * @property int $admin_permission_id
 */
class AdminRolePermission extends Pivot
{
    protected $autoWriteTimestamp = false;
    protected $table = 'admin_role_permission';
    protected $field = [
        'admin_role_id',
        'admin_permission_id',
    ];
    protected $pk = 'admin_role_id';

    protected $type = ['admin_role_id' => 'integer'];
}
