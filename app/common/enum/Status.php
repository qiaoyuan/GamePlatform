<?php

namespace app\common\enum;

enum Status: int
{
    case Enable = 1;
    case Disable = 0;

    public static function getMap(): array
    {
        return [
            self::Disable->value => '禁用',
            self::Enable->value => '启用',
        ];
    }
}
