<?php

namespace app\common\model;

/**
 * @property int $id
 * @property string $ip IP
 * @property int $admin_id 人员
 * @property string $created_at
 */
class AdminLoginLog extends Base
{
    protected $table = 'admin_login_log';
    protected $pk = 'id';
    protected $updateTime = false;
    protected $field = [
        'id',
        'ip',
        'admin_id',
        'created_at',
    ];
    protected $type = [];
}
