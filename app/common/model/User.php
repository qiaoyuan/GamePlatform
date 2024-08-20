<?php

namespace app\common\model;

/**
 * @property int $id
 * @property int $status
 * @property string $password
 * @property string $last_login_ip
 * @property string $last_login_at
 * @property string $username 账号
 * @property string $nickname 名称
 * @property string $phone 电话
 * @property int $channel_id 渠道id
 * @property string $open_id 对于微信商家唯一标
 */
class User extends Base
{
    protected $table = 'user';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;
    protected $field = [
        'id',
        'status',
        'password',
        'last_login_ip',
        'last_login_at',
        'username',
        'nickname',
        'phone',
        'channel_id',
        'open_id',
    ];
    protected $type = [
        'amount' => 'float',
        'frozen_amount' => 'float',
    ];

    public function info()
    {
        return $this->hasOne(UserInfo::class, 'user_id', 'id');
    }

    public function userThirds()
    {
        return $this->hasMany(UserThird::class, 'user_id', 'id');
    }

    public function userDevices()
    {
        return $this->hasMany(UserDevice::class, 'user_id', 'id');
    }
}
