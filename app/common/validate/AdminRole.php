<?php

namespace app\common\validate;

class AdminRole extends Base
{

    protected $rule = [
        'title|名称' => 'require|length:1,20|unique:admin_role,title,,id',
        'description|简介' => 'length:1,200',
    ];

    protected $message = [
    ];

    protected $scene = [
        'add' => ['title', 'description'],
        'edit' => ['title', 'description'],
    ];

    public function sceneEdit()
    {
        return $this->remove('title', 'unique')
            ->append('title', 'unique:admin_role,title,' . input('role_id') . ',id');
    }
}
