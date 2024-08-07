<?php

namespace app\common\enum;

enum SmsType: int
{
    case Other = 0;
    case Login = 1;
    case Bind = 2;
    case Forget = 3;
    case ChangePhone = 4;
    case VerifyPhone = 5;
    case ChangePassword = 6;
    case OrderChange = 7;

    public static function getMap(): array
    {
        return [
            self::Other->value => '其他',
            self::Login->value => '登录',
            self::Bind->value => '绑定',
            self::Forget->value => '忘记密码',
            self::VerifyPhone->value => '验证手机',
            self::ChangePhone->value => '修改手机',
            self::ChangePassword->value => '修改密码',
            self::OrderChange->value => '订单变更',
        ];
    }

    public static function getTemplateMap(): array
    {
        return [
            self::Other->value => 'SMS_113975019',
            self::Login->value => 'SMS_113975019',
            self::Bind->value => 'SMS_113975019',
            self::Forget->value => 'SMS_113975019',
            self::VerifyPhone->value => 'SMS_113975019',
            self::ChangePhone->value => 'SMS_113975019',
            self::ChangePassword->value => 'SMS_113975019',
            self::OrderChange->value => 'SMS_113975019',
        ];
    }

    public static function exists($value): bool
    {
        return isset(self::getMap()[$value]);
    }
}
