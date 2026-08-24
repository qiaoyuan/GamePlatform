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
        'client_id|Client ID'            => 'max:255',
        'client_secret|Client Secret'    => 'max:255',
        'status|状态'                    => 'require|in:0,1',
    ];

    protected $scene = [
        // G2G 三令牌与 Eldorado client_id/client_secret 均允许留空（按平台按需填写）
        'add'  => ['user_id', 'account_name', 'platform', 'active_device_token', 'long_lived_token', 'refresh_token', 'client_id', 'client_secret', 'status'],
        // platform 新增后不可改，edit 场景排除（与控制器 mEdit except 保持一致）
        'edit' => ['user_id', 'account_name', 'active_device_token', 'long_lived_token', 'refresh_token', 'client_id', 'client_secret', 'status', 'id'],
    ];
}
