<?php

namespace app\common\model;

/**
 * @property int $id
 * @property string $tab_name
 * @property string $param
 * @property string $rule
 * @property string $key
 */
class AdminColumn extends Base
{
    protected $table = 'admin_column';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;
    protected $field = [
        'id',
        'tab_name',
        'param',
        'rule',
        'key',
    ];
    protected $type = [
        'rule' => 'array'
    ];
}
