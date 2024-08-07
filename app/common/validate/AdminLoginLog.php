<?php

namespace app\common\validate;

class AdminLoginLog extends Base
{
    protected $rule = [
        'ip|IP' => ['require'],
        'admin_id|人员' => ['require'],
    ];

    protected $message = [];

    protected $scene = [
        'add' => ['ip', 'admin_id'],
        'edit' => ['ip', 'admin_id', 'id'],
    ];
}
