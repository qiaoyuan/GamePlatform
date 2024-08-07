<?php

namespace app\common\model;

/**
 * @property int $user_id
 * @property string $register_at 注册时间
 * @property string $last_login_at 最后登录时间
 * @property int $login_count 登录次数
 * @property string $last_login_ip 上次登录IP
 * @property string $real_name 真实姓名
 * @property string $id_card_no 身份证
 * @property string $wechat 微信号
 * @property string $alipay 支付宝账号
 * @property string $invite_code 邀请码
 */
class UserInfo extends Base
{
    protected $table = 'user_info';
    protected $pk = 'user_id';
    protected $autoWriteTimestamp = false;
    protected $field = [
        'user_id',
        'register_at',
        'last_login_at',
        'login_count',
        'last_login_ip',
        'real_name',
        'id_card_no',
        'wechat',
        'alipay',
        'invite_code',
    ];
    protected $type = [];
}
