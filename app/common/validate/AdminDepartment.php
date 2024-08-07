<?php

namespace app\common\validate;

class AdminDepartment extends Base
{
    protected $rule = [
        'title|名称' => ['require'],
        'description|描述' => [],
        'parent_id|上级部门' => [],
        'admin_id|部门负责人' => ['require'],
    ];

    protected $message = [];

    protected $scene = [
        'add' => ['title', 'description', 'parent_id', 'admin_id'],
        'edit' => ['title', 'description', 'parent_id', 'admin_id', 'id'],
    ];
}
