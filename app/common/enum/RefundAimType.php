<?php

namespace app\common\enum;

enum RefundAimType: int
{
    case Order = 0;

    public static function getMap(): array
    {
        return [
            self::Order->value => '下单失败',
        ];
    }

    public static function generateRefundNo(int $payAimType = 0): string
    {
        return PayAimType::generatePayNo($payAimType);
    }
}
