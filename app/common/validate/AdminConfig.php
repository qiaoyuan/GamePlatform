<?php

namespace app\common\validate;

class AdminConfig extends Base
{
    protected $rule = [
        'name|配置标识' => ['require'],
        'group|分组' => 'require'
    ];

    protected $message = [
    ];

    protected $scene = [
        'save' => ['name', 'group'],
    ];
}
