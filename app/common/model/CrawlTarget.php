<?php
declare(strict_types=1);

namespace app\common\model;

use think\model\relation\BelongsTo;

/**
 * 爬取目标链接
 *
 * @property int    $id
 * @property int    $game_product_id 关联游戏产品ID
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
    protected $field = ['id', 'game_product_id', 'name', 'url', 'category', 'status', 'last_crawl_at', 'created_at', 'updated_at', 'deleted_at'];

    /** @var array<string, string> */
    protected $type = [
        'id'             => 'int',
        'game_product_id' => 'int',
        'name'           => 'string',
        'url'           => 'string',
        'category'      => 'string',
        'status'        => 'int',
        'last_crawl_at' => 'string',
        'created_at'    => 'string',
        'updated_at'    => 'string',
        'deleted_at'    => 'string',
    ];

    // ==================== 产品分类枚举 ====================

    /** G2G 物品分类，保留历史数据库值以兼容现有爬取目标 */
    const CATEGORY_ITEM = '物品';
    /** G2G 游戏币分类 */
    const CATEGORY_CURRENCY = '游戏币';

    /** @var array<string, string> 产品分类显示文案 */
    public static $CATEGORY_MAP = [
        self::CATEGORY_ITEM     => 'G2G物品',
        self::CATEGORY_CURRENCY => 'G2G游戏币',
    ];

    public static function getCategoryList(): array
    {
        return [
            ['value' => self::CATEGORY_ITEM, 'label' => 'G2G物品'],
            ['value' => self::CATEGORY_CURRENCY, 'label' => 'G2G游戏币'],
        ];
    }

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

    public function gameProduct(): BelongsTo
    {
        return $this->belongsTo(GameProduct::class, 'game_product_id', 'id');
    }
}
