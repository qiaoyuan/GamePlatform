<?php
namespace Pay;

use Yansongda\Pay\Gateways;
use Yansongda\Pay\Pay;

class Factory
{
    const ALIPAY_SUCCESS = 'TRADE_SUCCESS';
    const ALIPAY_FINISHED = 'TRADE_FINISHED';
    const WECHAT_SUCCESS = 'SUCCESS';

    public static function alipay(): Gateways\Alipay
    {
        return Pay::alipay(config('pay.alipay'));
    }

    public static function wechat(): Gateways\Wechat
    {
        return Pay::wechat(config('pay.wechat'));
    }

    public static function payment(array $config)
    {
    }
}