<?php

namespace app\common\validate;

class AdminPermission extends Base
{
    protected $rule = [
        'title|名称' => 'require|length:1,15',
        'parent_id|上级权限' => 'integer|min:1',
        'url|权限' => 'require|length:2,50|unique:admin_permission,url,,id',
    ];

    protected $message = [
        'url.unique' => '权限已经存在',
    ];

    protected $scene = [
        'add' => ['title', 'parent_id', 'url'],
    ];

    public function sceneEdit()
    {
        return $this->remove('title', 'require')
            ->remove('url', 'unique')
            ->append('url', 'unique:admin_permission,url,' . input('permission_id') . ',id');
    }
}
