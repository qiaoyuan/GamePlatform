<?php

namespace app\common\enum;

enum OrderPayStatus: int
{
    case Pending = 0;
    case Paid = 1;
    case Paying = 2;
    case Cancel = 3;
    case RefundPart = 4;
    case RefundAll = 5;
    case Frozen = 6;
    case Unfreeze = 7;
    case FreezePay = 8;

    public static function getMap(): array
    {
        return [
            self::Pending->value => '待支付',
            self::Paid->value => '已支付',
            self::Paying->value => '支付中',
            self::Cancel->value => '已取消',
            self::RefundPart->value => '部分退款',
            self::RefundAll->value => '全额退款',
            self::Frozen->value => '预授权成功',
            self::Unfreeze->value => '预授权取消',
            self::FreezePay->value => '预授权支付',
        ];
    }
}
