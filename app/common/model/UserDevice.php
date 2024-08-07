<?php

namespace app\common\model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $created_at 首次登录时间
 * @property string $last_use_at 最近使用时间
 * @property string $last_use_ip
 * @property string $device_id 设备码
 * @property int $status 是否信任
 */
class UserDevice extends Base
{
    protected $table = 'user_device';
    protected $pk = 'id';
    protected $updateTime = false;
    protected $field = [
        'id',
        'user_id',
        'created_at',
        'last_use_at',
        'last_use_ip',
        'device_id',
        'status',
    ];
    protected $type = [];
}
