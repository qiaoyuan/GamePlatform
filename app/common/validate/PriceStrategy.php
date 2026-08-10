<?php
declare(strict_types=1);

namespace app\common\validate;

class PriceStrategy extends Base
{
    protected $rule = [
        'name|策略名称'       => 'require|length:2,64',
        'crawl_target_id|对标竞品池' => 'require|number|gt:0',
        'auto_run|自动执行'   => 'in:0,1',
        'status|状态'         => 'require|in:0,1',
    ];

    protected $scene = [
        // config 为维度配置(JSON)，结构灵活，由 Controller/Service 兜底默认值，不在此做强校验
        'add'  => ['name', 'crawl_target_id', 'auto_run', 'status'],
        'edit' => ['name', 'crawl_target_id', 'auto_run', 'status', 'id'],
    ];
}
