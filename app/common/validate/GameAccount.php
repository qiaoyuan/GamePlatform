<?php
declare(strict_types=1);

namespace app\common\validate;

use app\common\validate\Base;

class GameAccount extends Base
{
    protected $rule = [
        'account|账号'  => 'require|length:2,32',
        'password|密码' => 'require|length:6,32',
        'status|状态'   => 'require|in:0,1',
        'remark|备注'   => 'max:256',
    ];

    protected $scene = [
        'add'  => ['account', 'password', 'status', 'remark'],
        'edit' => ['account', 'password', 'status', 'remark', 'id'],
    ];
}
