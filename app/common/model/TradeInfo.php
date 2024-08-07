<?php

namespace app\common\model;

/**
 * @property int $id
 * @property array $content
 * @property string $type 类型
 * @property string $trade_no
 * @property string $payment_type
 */
class TradeInfo extends Base
{
    
    protected $table = 'trade_info';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;
    protected $field = [
        'id',
        'content',
        'type',
        'trade_no',
        'payment_type',
    ];
    protected $type = [
        'content' => 'array'
    ];
}
