<?php
declare(strict_types=1);

namespace app\common\validate;

use app\common\validate\Base;

class GameAccount extends Base
{
    protected $rule = [
        'user_id|用户ID'                 => 'require|length:1,64',
        'account_name|账号名称'           => 'max:64',
        'platform|平台'                  => 'require|number',
        'active_device_token|设备活跃令牌' => 'max:255',
        'long_lived_token|长期访问令牌'    => 'max:255',
        'refresh_token|刷新令牌'          => 'max:255',
        'status|状态'                    => 'require|in:0,1',
    ];

    protected $scene = [
        // account_name 与三个 token 允许留空（后续补录），user_id/platform/status 必填
        'add'  => ['user_id', 'account_name', 'platform', 'active_device_token', 'long_lived_token', 'refresh_token', 'status'],
        'edit' => ['user_id', 'account_name', 'platform', 'active_device_token', 'long_lived_token', 'refresh_token', 'status', 'id'],
    ];
}
