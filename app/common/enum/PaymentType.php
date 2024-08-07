<?php

namespace app\common\enum;

enum PaymentType
{
    case Alipay;
    case Wechat;
    case Amount;

    public static function getMap(): array
    {
        return [
            self::Alipay->name => '支付宝',
            self::Wechat->name => '微信',
            self::Amount->name => '余额',
        ];
    }

    public function isSuccess(array $result): bool
    {
        return match ($this->name) {
            self::Alipay->name => $result['trade_status'] == 'TRADE_SUCCESS' || $result['trade_status'] == 'TRADE_FINISHED',
            self::Wechat->name => $result['event_type'] == 'TRANSACTION.SUCCESS',
            default => true,
        };
    }

    public function isFreezeSuccess(array $result): bool
    {
        return match ($this->name) {
            self::Alipay->name => $result['status'] == 'SUCCESS' && !empty($result['auth_no']),
            self::Wechat->name => $result['event_type'] == 'TRANSACTION.SUCCESS',
            default => true,
        };
    }

    public function getTradeNo(array $result): string
    {
        return match ($this->name) {
            self::Alipay->name => $result['trade_no'],
            self::Wechat->name => $result['resource']['ciphertext']['transaction_id'] ?? '',
            default => '',
        };
    }

    public function getAuthNo(array $result): string
    {
        return match ($this->name) {
            self::Alipay->name => $result['auth_no'],
            self::Wechat->name => $result['resource']['ciphertext']['auth_no'] ?? '',
            default => '',
        };
    }

    public function getPayNo(array $result): string
    {
        $payNo = '';
        switch ($this->name) {
            case self::Alipay->name:
                $payNo = $result['out_trade_no'] ?? $result['out_order_no'];
                break;
            case self::Wechat->name:
                $payNo = $result['resource']['ciphertext']['out_trade_no'] ?? '';
                break;
        }
        return $payNo ? substr($payNo, 0, -2) : '';
    }

    public function getPayAmount(array $result): float
    {
        if ($this->name == self::Wechat->name) {
            $amount = $result['resource']['ciphertext']['amount']['total'] ?? 0;
            $amount = bcdiv($amount, 100, 2);
        } else {
            $amount =  $result['total_amount'] ?? 0;
        }
        return floatval($amount);
    }

    public function getRefundTradeNo(array $result): string
    {
        return match ($this->name) {
            self::Alipay->name => $result['trade_no'],
            self::Wechat->name => $result['transaction_id'] ?? '',
            default => '',
        };
    }

    public function isRefundSuccess(array $result): bool
    {
        return match ($this->name) {
            self::Alipay->name => $result['code'] == '10000',
            self::Wechat->name => $result['status'] == 'SUCCESS' || $result['status'] == 'PROCESSING',
            default => true,
        };
    }
}
