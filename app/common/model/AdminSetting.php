<?php

namespace app\common\model;

/**
 * @property int $id
 * @property string $module 模块
 * @property string $title 名称
 * @property int $sort 排序
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 */
class AdminSetting extends Base
{
    protected $table = 'admin_setting';
    protected $pk = 'id';
    protected $field = [
        'id',
        'module',
        'title',
        'sort',
        'created_at',
        'updated_at',
        'status',
    ];
    protected $type = [];

    public static array $moduleMap = [
        'workType' => '工单类型',
        'priority' => '优先级',
    ];
}
