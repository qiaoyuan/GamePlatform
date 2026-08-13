<?php
declare(strict_types=1);

namespace app\common\validate;

use app\common\model\CrawlTarget as CrawlTargetModel;

class CrawlTarget extends Base
{
    protected $rule = [
        'name|任务名称'     => 'require|length:2,64',
        'url|目标链接'       => 'require|url|max:1024',
        'category|产品分类'  => 'require|in:' . CrawlTargetModel::CATEGORY_ITEM . ',' . CrawlTargetModel::CATEGORY_CURRENCY,
        'status|状态'        => 'require|in:0,1',
    ];

    protected $scene = [
        'add'  => ['name', 'url', 'category', 'status'],
        'edit' => ['name', 'url', 'category', 'status', 'id'],
    ];
}
