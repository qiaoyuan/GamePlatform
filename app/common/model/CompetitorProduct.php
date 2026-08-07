<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 竞品产品分析
 *
 * @property int    $id
 * @property int    $crawl_target_id 爬取目标ID
 * @property string $store_name      店铺唯一标识
 * @property string $store_url       店铺链接
 * @property string $store_level     店铺等级
 * @property string $stock           库存
 * @property float  $price           销售单价
 * @property string $currency        币种
 * @property string $crawl_at        爬取时间
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class CompetitorProduct extends Base
{
    protected $table = 'competitor_product';
    protected $pk    = 'id';

    /** @var string[] */
    protected $field = ['id', 'crawl_target_id', 'store_name', 'store_url', 'store_level', 'stock', 'price', 'currency', 'crawl_at', 'created_at', 'updated_at', 'deleted_at'];

    /** @var array<string, string> */
    protected $type = [
        'id'              => 'int',
        'crawl_target_id' => 'int',
        'store_name'      => 'string',
        'store_url'       => 'string',
        'store_level'     => 'string',
        'stock'           => 'string',
        'price'           => 'float',
        'currency'        => 'string',
        'crawl_at'        => 'string',
        'created_at'      => 'string',
        'updated_at'      => 'string',
        'deleted_at'      => 'string',
    ];

    /**
     * 关联爬取目标
     */
    public function crawlTarget(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(CrawlTarget::class, 'crawl_target_id', 'id');
    }
}
