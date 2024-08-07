<?php

namespace app\common\model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $type 三方登陆类型
 * @property string $openid
 * @property string $name 三方昵称
 * @property string $unionid 微信，QQ，小程序等唯一ID
 * @property string $avatar 头像
 * @property string $updated_at
 * @property string $created_at
 */
class UserThird extends Base
{
    protected $table = 'user_third';
    protected $pk = 'id';
    protected $field = [
        'id',
        'user_id',
        'type',
        'openid',
        'name',
        'unionid',
        'avatar',
        'updated_at',
        'created_at',
    ];
    protected $type = [];
}
