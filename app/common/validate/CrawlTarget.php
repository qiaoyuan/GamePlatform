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
        'game_product_id|游戏产品' => 'require|number|gt:0',
        'status|状态'        => 'require|in:0,1',
    ];

    protected $scene = [
        'add'  => ['name', 'url', 'category', 'game_product_id', 'status'],
        'edit' => ['name', 'url', 'category', 'game_product_id', 'status', 'id'],
    ];
}
