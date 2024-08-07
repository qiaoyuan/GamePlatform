<?php

namespace app\common\model;

/**
 * @property int $id
 * @property string $nickname 昵称
 * @property string $avatar 头像
 * @property string $phone 电话
 * @property string $password
 * @property int $point 积分
 * @property int $total_point 累计获得积分
 * @property float $amount 余额
 * @property float $frozen_amount 冻结余额
 * @property int $frozen_type 冻结类型（下单，提现）
 * @property int $invite_by_id 邀请人
 * @property int $is_id_card_verify 是否实名认证
 */
class User extends Base
{
    protected $table = 'user';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;
    protected $field = [
        'id',
        'nickname',
        'avatar',
        'phone',
        'password',
        'point',
        'total_point',
        'amount',
        'frozen_amount',
        'frozen_type',
        'invite_by_id',
        'is_id_card_verify',
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
