<?php

namespace app\common\validate;

class Admin extends Base
{
    protected $rule = [
        'username|账号' => 'require',
        'password|密码' => 'require|min:6|max:30',
        'captcha|验证码'=>'require',
        'opassword|原始密码' => 'require',
        'rpassword|重复密码' => 'require|confirm:password',
        'nickname|名称' => 'require'
    ];

    protected $message = [
    ];

    protected $scene = [
        'login' => ['username', 'password'],
        'password' => ['password', 'opassword', 'rpassword'],
        'add' => ['username', 'nickname'],
        'edit' => ['username', 'nickname']
    ];
}
