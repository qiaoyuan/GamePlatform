<?php

namespace app\common\model;

use think\model\Pivot;
use think\model\relation\BelongsTo;
use think\model\relation\BelongsToMany;

/**
 * @property int $admin_id
 * @property int $admin_role_id
 */
class AdminRoleAdmin extends Pivot
{
    protected $autoWriteTimestamp = false;
    protected $table = 'admin_role_admin';
    protected $field = [
        'admin_id',
        'admin_role_id',
    ];
    protected $pk = 'admin_id';

    protected $type = ['admin_id' => 'integer'];

    public function role(): BelongsTo
    {
        return $this->belongsTo('AdminRole', 'admin_role_id', 'id');
    }

    public function permissions(): BelongsTo
    {
        return $this->belongsTo('AdminPermission', 'admin_role_permission', 'admin_permission_id', 'admin_role_id');
    }

    public function admins(): BelongsTo
    {
        return $this->belongsTo(Admin::class,'admin_id','id');
    }
}
