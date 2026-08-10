<?php
declare(strict_types=1);

namespace app\common\model;

use think\model\relation\BelongsTo;

/**
 * 改价策略-产品绑定（game_product_id 唯一，保证一个产品只归属一套策略）
 *
 * @property int    $id
 * @property int    $price_strategy_id 策略ID
 * @property int    $game_product_id   游戏产品ID
 * @property string $created_at
 * @property string $updated_at
 */
class PriceStrategyProduct extends Base
{
    protected $table = 'price_strategy_product';
    protected $pk    = 'id';

    /** @var string[] */
    protected $field = ['id', 'price_strategy_id', 'game_product_id', 'created_at', 'updated_at'];

    /** @var array<string, string> */
    protected $type = [
        'id'                => 'int',
        'price_strategy_id' => 'int',
        'game_product_id'   => 'int',
        'created_at'        => 'string',
        'updated_at'        => 'string',
    ];

    /**
     * 关联策略
     */
    public function strategy(): BelongsTo
    {
        return $this->belongsTo(PriceStrategy::class, 'price_strategy_id', 'id');
    }

    /**
     * 关联游戏产品
     */
    public function gameProduct(): BelongsTo
    {
        return $this->belongsTo(GameProduct::class, 'game_product_id', 'id');
    }
}
