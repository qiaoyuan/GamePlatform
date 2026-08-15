<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 爬取数据（真实竞品，由 Python 爬虫写入 crawl_data 表）
 *
 * @property int    $id
 * @property int    $target_id       关联 crawl_target.id
 * @property int    $game_product_id 关联游戏产品ID
 * @property int    $version         爬虫数据版本
 * @property string $platform        平台 g2g/eldorado
 * @property string $seller_id       店铺唯一标识
 * @property string $seller_name     店铺显示名
 * @property string $seller_level    卖家等级
 * @property string $seller_url      店铺链接
 * @property int    $is_online       是否在线
 * @property string $product_title   产品标题
 * @property string $offer_url       产品链接
 * @property string $sold_count      已售(原始文本)
 * @property int    $sold_count_num  已售(数字)
 * @property string $stock           库存(原始文本)
 * @property int    $stock_num       库存(数字)
 * @property float  $price           单价
 * @property string $currency        货币
 * @property string $min_order       最低起订
 * @property string $delivery_time   交货时间
 * @property string $rating         好评率（如 96.00）
 * @property string $crawled_at      爬取时间
 * @property string $created_at
 */
class CrawlData extends Base
{
    protected $table = 'crawl_data';
    protected $pk    = 'id';

    // crawl_data 表只有 created_at(默认值)，无 updated_at / deleted_at
    protected $autoWriteTimestamp = false;

    /** @var string[] */
    protected $field = [
        'id', 'target_id', 'game_product_id', 'version', 'platform', 'seller_id', 'seller_name', 'seller_level', 'seller_url',
        'is_online', 'product_title', 'offer_url', 'sold_count', 'sold_count_num', 'stock', 'stock_num',
        'price', 'currency', 'min_order', 'delivery_time', 'rating', 'crawled_at', 'created_at',
    ];

    /** @var array<string, string> */
    protected $type = [
        'id'             => 'int',
        'target_id'      => 'int',
        'game_product_id' => 'int',
        'version'        => 'int',
        'is_online'      => 'int',
        'sold_count_num' => 'int',
        'stock_num'      => 'int',
        'price'          => 'float',
    ];

    /**
     * 关联爬取目标
     */
    public function crawlTarget(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(CrawlTarget::class, 'target_id', 'id');
    }
}
