<?php
declare(strict_types=1);

namespace app\common\model;

use think\model\relation\BelongsTo;

/**
 * 爬取完成通知（Python 爬完写入，PHP 消费后执行改价策略）
 *
 * @property int    $id
 * @property int    $crawl_target_id 爬取目标ID
 * @property int    $version         本次爬取数据版本
 * @property int    $crawled_count   本次爬取条数
 * @property int    $status          处理状态 0-待处理 1-已处理 2-处理失败 3-处理中
 * @property int    $attempts        已领取次数
 * @property string $available_at    下次可领取时间
 * @property string $started_at      本次领取时间
 * @property string $heartbeat_at    Worker 最近心跳时间
 * @property string $worker_id       当前 Worker 标识
 * @property string $dedupe_key      目标和版本组成的幂等键
 * @property string $message         处理结果说明
 * @property string $crawled_at      Python 爬取完成时间
 * @property string $processed_at    PHP 处理时间
 * @property string $created_at
 * @property string $updated_at
 */
class CrawlNotify extends Base
{
    protected $table = 'crawl_notify';
    protected $pk    = 'id';

    /** @var string[] */
    protected $field = [
        'id', 'crawl_target_id', 'version', 'crawled_count', 'status', 'attempts',
        'available_at', 'started_at', 'heartbeat_at', 'worker_id', 'dedupe_key',
        'message', 'crawled_at', 'processed_at', 'created_at', 'updated_at',
    ];

    /** @var array<string, string> */
    protected $type = [
        'id'              => 'int',
        'crawl_target_id' => 'int',
        'version'         => 'int',
        'crawled_count'   => 'int',
        'status'          => 'int',
        'attempts'        => 'int',
        'message'         => 'string',
        'crawled_at'      => 'string',
        'processed_at'    => 'string',
        'created_at'      => 'string',
        'updated_at'      => 'string',
    ];

    // ==================== 枚举 ====================

    /** 待处理 */
    const STATUS_PENDING = 0;
    /** 已处理 */
    const STATUS_DONE = 1;
    /** 处理失败 */
    const STATUS_FAIL = 2;
    /** Worker 已领取，正在处理 */
    const STATUS_PROCESSING = 3;

    /** @var array<int, string> */
    public static $STATUS_MAP = [
        self::STATUS_PENDING => '待处理',
        self::STATUS_DONE    => '已处理',
        self::STATUS_FAIL    => '处理失败',
        self::STATUS_PROCESSING => '处理中',
    ];

    public static function getStatusList(): array
    {
        return [
            ['value' => self::STATUS_PENDING, 'label' => '待处理'],
            ['value' => self::STATUS_DONE,    'label' => '已处理'],
            ['value' => self::STATUS_FAIL,    'label' => '处理失败'],
            ['value' => self::STATUS_PROCESSING, 'label' => '处理中'],
        ];
    }

    public function crawlTarget(): BelongsTo
    {
        return $this->belongsTo(CrawlTarget::class, 'crawl_target_id', 'id');
    }
}
