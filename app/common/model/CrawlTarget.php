<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 爬取目标链接
 *
 * @property int    $id
 * @property string $name     任务名称
 * @property string $url      目标链接
 * @property string $category 产品分类
 * @property int    $status   状态 0-停用 1-启用
 * @property string $last_crawl_at 最后爬取时间
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class CrawlTarget extends Base
{
    protected $table = 'crawl_target';
    protected $pk    = 'id';

    /** @var string[] */
    protected $field = ['id', 'name', 'url', 'category', 'status', 'last_crawl_at', 'created_at', 'updated_at', 'deleted_at'];

    /** @var array<string, string> */
    protected $type = [
        'id'            => 'int',
        'name'          => 'string',
        'url'           => 'string',
        'category'      => 'string',
        'status'        => 'int',
        'last_crawl_at' => 'string',
        'created_at'    => 'string',
        'updated_at'    => 'string',
        'deleted_at'    => 'string',
    ];

    // ==================== 枚举 ====================

    /** 状态：停用 */
    const STATUS_OFF = 0;
    /** 状态：启用 */
    const STATUS_ON  = 1;

    /** @var array<int, string> */
    public static $STATUS_MAP = [
        self::STATUS_OFF => '停用',
        self::STATUS_ON  => '启用',
    ];

    public static function getStatusList(): array
    {
        return [
            ['value' => self::STATUS_OFF, 'label' => '停用'],
            ['value' => self::STATUS_ON,  'label' => '启用'],
        ];
    }
}
