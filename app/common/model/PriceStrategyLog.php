<?php
declare(strict_types=1);

namespace app\common\model;

use think\model\relation\BelongsTo;

/**
 * 改价策略执行日志
 *
 * @property int    $id
 * @property int    $price_strategy_id 策略ID
 * @property int    $game_product_id   游戏产品ID
 * @property int|null $competitor_id   竞品数据ID（crawl_data.id）
 * @property float  $old_price         改价前价格
 * @property float  $new_price         改价后价格
 * @property float  $ref_price         参考价(竞品最低价)
 * @property int    $status            结果 0-跳过 1-成功 2-失败
 * @property string $message           说明/原因
 * @property string $created_at
 */
class PriceStrategyLog extends Base
{
    protected $table = 'price_strategy_log';
    protected $pk    = 'id';

    // 日志表只有 created_at，无 updated_at，需关闭更新时间自动写入
    protected $updateTime = false;

    /** @var string[] */
    protected $field = ['id', 'price_strategy_id', 'game_product_id', 'competitor_id', 'old_price', 'new_price', 'ref_price', 'status', 'message', 'created_at'];

    /** @var array<string, string> */
    protected $type = [
        'id'                => 'int',
        'price_strategy_id' => 'int',
        'game_product_id'   => 'int',
        'competitor_id'     => 'int',
        'old_price'         => 'float',
        'new_price'         => 'float',
        'ref_price'         => 'float',
        'status'            => 'int',
        'message'           => 'string',
        'created_at'        => 'string',
    ];

    // ==================== 枚举 ====================

    /** 结果：跳过 */
    const STATUS_SKIP = 0;
    /** 结果：成功 */
    const STATUS_SUCCESS = 1;
    /** 结果：失败 */
    const STATUS_FAIL = 2;

    /** @var array<int, string> */
    public static $STATUS_MAP = [
        self::STATUS_SKIP    => '跳过',
        self::STATUS_SUCCESS => '成功',
        self::STATUS_FAIL    => '失败',
    ];

    public static function getStatusList(): array
    {
        return [
            ['value' => self::STATUS_SKIP,    'label' => '跳过'],
            ['value' => self::STATUS_SUCCESS, 'label' => '成功'],
            ['value' => self::STATUS_FAIL,    'label' => '失败'],
        ];
    }

    /**
     * 记录一条日志
     */
    public static function record(array $data): void
    {
        $log = new self;
        $log->save(array_merge(['created_at' => date('Y-m-d H:i:s')], $data));
    }

    // ==================== 关联 ====================

    public function strategy(): BelongsTo
    {
        return $this->belongsTo(PriceStrategy::class, 'price_strategy_id', 'id');
    }

    public function gameProduct(): BelongsTo
    {
        return $this->belongsTo(GameProduct::class, 'game_product_id', 'id');
    }
}
