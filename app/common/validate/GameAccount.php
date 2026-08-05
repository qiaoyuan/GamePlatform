<?php

namespace app\common\validate;

class GameAccount extends Base
{
    protected $rule = [
        'user_id|用户ID' => ['require'],
        'account_name|账号名称' => [],
        'platform|平台' => ['require'],
        'active_device_token|设备活跃令牌' => [],
        'long_lived_token|长期访问令牌' => [],
        'refresh_token|刷新令牌' => [],
        'status|状态' => ['require'],
    ];

    protected $message = [];

    protected $scene = [
        'add' => ['user_id', 'account_name', 'platform', 'active_device_token', 'long_lived_token', 'refresh_token', 'status'],
        'edit' => ['user_id', 'account_name', 'platform', 'active_device_token', 'long_lived_token', 'refresh_token', 'status', 'id'],
    ];
}
