<?php

namespace app\common\enum;

enum PayAimType: int
{
    case Order = 0;

    public static function getMap(): array
    {
        return [
            self::Order->value => '下单',
        ];
    }

    public static function getPayAimType(int $type): PayAimType
    {
        return match ($type) {
            self::Order->value => self::Order,
        };
    }

    public static function generatePayNo(int $payAimType = 0): string
    {
        $first = match ($payAimType) {
            self::Order->value => 'O',
        };
        return $first . date('ymd') . substr(time(), -5) . str_pad((string)mt_rand(0,9999), 4, 0, STR_PAD_LEFT);
    }
}
