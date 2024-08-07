<?php

namespace app\common\validate;

class AdminSetting extends Base
{
    protected $rule = [
        'title|名称' => ['require'],
        'sort|排序' => ['integer'],
    ];

    protected $message = [];

    protected $scene = [
        'add' => ['title', 'sort'],
        'edit' => ['title', 'sort', 'id'],
    ];
}
