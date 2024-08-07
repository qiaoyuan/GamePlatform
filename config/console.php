<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
use app\common\command\Controller;
use app\common\command\Domain;
use app\common\command\Model;
use app\common\command\Tool;
use app\common\command\Validate;
use app\common\command\Permission;
use app\common\command\View;

return [
    // 指令定义
    'commands' => [
        'model' => Model::class,
        'view' => View::class,
        'controller' => Controller::class,
        'validate' => Validate::class,
        'tool' => Tool::class,
        'permission' => Permission::class,
        'domain' => Domain::class,
    ],
];
