<?php
namespace Wechat;

use util\MyCacheDriver;

class Factory
{
    public static function miniProgram(): \EasyWeChat\MiniProgram\Application
    {
        $app = \EasyWeChat\Factory::miniProgram(config('wechat.mini'));
        $app->rebind('cache', new MyCacheDriver());
        return $app;
    }

    public static function mp(): \EasyWeChat\OfficialAccount\Application
    {
        $app = \EasyWeChat\Factory::officialAccount(config('wechat.mp'));
        $app->rebind('cache', new MyCacheDriver());
        return $app;
    }

    public static function pay(): \EasyWeChat\Payment\Application
    {
        $app = \EasyWeChat\Factory::payment(config('wechat.pay'));
        $app->rebind('cache', new MyCacheDriver());
        return $app;
    }

    public static function getMiniPhone(string $code): string
    {
        try {
            $decryptedData = Factory::miniProgram()->phone_number->getUserPhoneNumber($code);
            return $decryptedData['phone_info']['purePhoneNumber'] ?? '';
        } catch (\Throwable $e) {
            //ignore
        }
        return '';
    }
}