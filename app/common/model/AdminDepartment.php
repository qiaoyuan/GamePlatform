<?php

namespace app\common\model;

/**
 * @property int $id
 * @property string $title 名称
 * @property string $description 描述
 * @property int $parent_id 上级部门
 * @property int $admin_id 部门负责人
 * @property string $created_at
 * @property string $updated_at
 * @property int $level
 */
class AdminDepartment extends Base
{
    protected $table = 'admin_department';
    protected $pk = 'id';
    protected $field = [
        'id',
        'title',
        'description',
        'parent_id',
        'admin_id',
        'created_at',
        'updated_at',
        'level',
    ];
    protected $type = [];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }
}
