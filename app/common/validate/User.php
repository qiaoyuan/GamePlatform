<?php

namespace app\common\validate;

class User extends Base
{
    protected $rule = [
        'nickname|昵称' => ['require'],
        'avatar|头像' => ['require'],
        'phone|电话' => ['require'],
        'real_name|真实姓名' => [],
        'id_card_no|身份证' => ['idCard'],
        'wechat|微信号' => [],
        'alipay|支付宝账号' => [],
    ];

    protected $message = [];

    protected $scene = [
        'add' => [
            'nickname',
            'avatar',
            'phone',
            'id_card_no'
        ],
        'edit' => [
            'nickname',
            'avatar',
            'phone',
            'id_card_no',
            'id'
        ],
    ];
}
