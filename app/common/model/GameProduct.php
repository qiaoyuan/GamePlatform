<?php

namespace app\common\model;

use think\model\relation\BelongsTo;

/**
 * @property int $id
 * @property int $game_account_id 关联游戏账号id
 * @property string $product_id 产品ID(自有)
 * @property string $title 产品名称
 * @property int $platform 平台 1:G2G
 * @property float $price 价格
 * @property int $stock 库存
 * @property string $currency 货币
 * @property int $sold_count 已出售数
 * @property float $sales_amount 销售金额
 * @property int $status 状态
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class GameProduct extends Base
{
    protected $table = 'game_product';
    protected $pk = 'id';
    protected $field = [
        'id',
        'game_account_id',
        'product_id',
        'title',
        'platform',
        'price',
        'stock',
        'currency',
        'sold_count',
        'sales_amount',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    protected $type = [
        'price' => 'float',
        'sales_amount' => 'float',
    ];

    const DEFAULT_CURRENCY = 'USD';

    public function gameAccount(): BelongsTo
    {
        return $this->belongsTo(GameAccount::class, 'game_account_id', 'id');
    }
}
