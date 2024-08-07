<?php

namespace app\common\enum;

enum OrderRefundStatus: int
{
    case Verifying = 0;
    case Paid = 1;
    case Cancel = 2;
    case Pending = 3;
    case Fail = 4;
    case PayFail = 5;

    public static function getMap(): array
    {
        return [
            self::Verifying->value => '待审核',
            self::Paid->value => '已退款',
            self::Cancel->value => '已取消',
            self::Pending->value => '待打款',
            self::Fail->value => '审核失败',
            self::PayFail->value => '打款失败',
        ];
    }
}
