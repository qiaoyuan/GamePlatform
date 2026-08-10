<?php
declare(strict_types=1);

namespace app\common\model;

use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

/**
 * 改价策略模板
 *
 * @property int    $id
 * @property string $name            策略名称
 * @property int    $crawl_target_id 对标竞品池(爬取目标ID)
 * @property array  $config          维度配置(JSON)
 * @property int    $auto_run        爬取完成后自动执行 0-否 1-是
 * @property int    $status          状态 0-停用 1-启用
 * @property string $last_run_at     最后执行时间
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class PriceStrategy extends Base
{
    protected $table = 'price_strategy';
    protected $pk    = 'id';

    /** @var string[] */
    protected $field = ['id', 'name', 'crawl_target_id', 'config', 'auto_run', 'interval_minutes', 'status', 'last_run_at', 'created_at', 'updated_at', 'deleted_at'];

    /** @var array<string, string> */
    protected $type = [
        'id'               => 'int',
        'name'             => 'string',
        'crawl_target_id'  => 'int',
        'config'           => 'json',
        'auto_run'         => 'int',
        'interval_minutes' => 'int',
        'status'           => 'int',
        'last_run_at'      => 'string',
        'created_at'       => 'string',
        'updated_at'       => 'string',
        'deleted_at'       => 'string',
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

    // ==================== 维度类型 ====================

    /** 维度：跟竞品最低价 */
    const DIMENSION_LOWEST = 'lowest';

    /** 竞价方式：幅度值（出价 = 目标价 - 幅度值，负幅度即加价） */
    const BID_AMOUNT = 'amount';
    /** 竞价方式：等值（与目标店铺同价） */
    const BID_EQUAL = 'equal';

    // ==================== 关联 ====================

    /**
     * 对标竞品池
     */
    public function crawlTarget(): BelongsTo
    {
        return $this->belongsTo(CrawlTarget::class, 'crawl_target_id', 'id');
    }

    /**
     * 绑定的产品
     */
    public function products(): HasMany
    {
        return $this->hasMany(PriceStrategyProduct::class, 'price_strategy_id', 'id');
    }
}
