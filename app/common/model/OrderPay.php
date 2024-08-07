<?php

namespace app\common\model;

use app\common\enum\OrderPayStatus;
use app\common\enum\PayAimType;
use app\common\enum\PaymentType;
use app\common\traits\UserWith;
use think\facade\App;
use think\facade\Log;
use think\helper\Str;
use Yansongda\Artful\Exception\InvalidResponseException;
use Yansongda\Artful\Plugin\ParserPlugin;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Plugin\Alipay\V2\AddPayloadSignaturePlugin;
use Yansongda\Pay\Plugin\Alipay\V2\FormatPayloadBizContentPlugin;
use Yansongda\Pay\Plugin\Alipay\V2\Pay\App\PayPlugin;
use Yansongda\Pay\Plugin\Alipay\V2\Pay\Authorization\Auth\AppFreezePlugin;
use Yansongda\Pay\Plugin\Alipay\V2\Pay\Authorization\Auth\UnfreezePlugin;
use Yansongda\Pay\Plugin\Alipay\V2\ResponseInvokeStringPlugin;
use Yansongda\Pay\Plugin\Alipay\V2\StartPlugin;
use Yansongda\Pay\Provider\Alipay;
use Yansongda\Pay\Provider\Wechat;

/**
 * @property int $id
 * @property int $trade_info_id
 * @property string $pay_no 支付单编号
 * @property int $status 支付状态
 * @property float $original_amount 原始金额
 * @property float $amount 金额
 * @property float $discount 折扣
 * @property int $user_coupon_id 优惠券
 * @property string $created_at
 * @property string $paid_at 支付时间
 * @property string $last_paying_at 最近一次调起支付时间
 * @property string $payment_type 支付方式（支付宝，微信，余额）
 * @property int $user_id
 * @property string $subject 支付标题
 * @property string $trade_no 流水号
 * @property int $pay_count
 * @property int $pay_aim_type 支付类型（下单，日本国内运费，验货拍照，合箱发货，国内运费）
 * @property float $refund_amount 退款金额
 * @property int $is_combine 是否是批量支付
 * @property int $is_freeze 是否是预授权
 */
class OrderPay extends Base
{
    use UserWith;
    protected $table = 'order_pay';
    protected $pk = 'id';
    protected $updateTime = false;
    protected $field = [
        'id',
        'trade_info_id',
        'pay_no',
        'status',
        'original_amount',
        'amount',
        'discount',
        'user_coupon_id',
        'created_at',
        'paid_at',
        'last_paying_at',
        'payment_type',
        'user_id',
        'subject',
        'trade_no',
        'pay_count',
        'pay_aim_type',
        'refund_amount',
        'is_combine',
        'is_freeze',
    ];
    protected $type = [
        'amount' => 'float',
    ];

    public static function checkCallback(array $result, PaymentType $type): bool
    {
        $log = Log::channel('pay_' . strtolower($type->name));
        $log->log('callback', json_encode($result, 320));
        $payNo = $type->getPayNo($result);
        $orderPay = OrderPay::where('pay_no', $payNo)->find();
        if (!$orderPay) {
            $log->error('订单不存在');
            return false;
        }
        if ($orderPay->is_freeze && !$type->isFreezeSuccess($result)) {
            return true;
        }
        if (!$orderPay->is_freeze && !$type->isSuccess($result)) {
            return true;
        }
        if ($orderPay->status != OrderPayStatus::Pending->value) {
            return true;
        }
        $r = transaction(function () use ($orderPay, $type, $result) {
            if ($orderPay->is_freeze) {
                $tradeNo = $type->getAuthNo($result);
            } else {
                $tradeNo = $type->getTradeNo($result);
            }
            $info = TradeInfo::create([
                'content' => $result,
                'type' => 'Pay',
                'trade_no' => $tradeNo,
                'payment_type' => $type->name,
            ]);
            $orderPay->payment_type = $type->name;
            $orderPay->trade_info_id = $info->id;
            $orderPay->trade_no = $tradeNo;
            $orderPay->status = $orderPay->is_freeze ? OrderPayStatus::Frozen->value : OrderPayStatus::Paid->value;
            $orderPay->paid_at = date('Y-m-d H:i:s');
            $orderPay->save();
            $orderPay->paySuccess();
        });
        $r && $log->error($r);
        return !$r;
    }

    public function canPay(): bool
    {
        return $this->status == OrderPayStatus::Pending->value || $this->status == OrderPayStatus::Paying->value;
    }

    public function paySuccess(): void
    {
    }

    public function app(): array
    {
        $log = Log::channel('pay_' . strtolower($this->payment_type));
        try {
            $this->pay_count += 1;
            $this->last_paying_at = dateNow();
            $this->status = OrderPayStatus::Paying->value;
            $this->save();
            if ($this->payment_type == PaymentType::Alipay->name) {
                $alipay = self::alipay()->app([
                    'out_trade_no' => $this->pay_no . str_pad((string)$this->pay_count, 2, '0', STR_PAD_LEFT),
                    'subject' => $this->subject,
                    'total_amount' => $this->amount
                ]);
                return $alipay->toArray();
            }
            if ($this->payment_type == PaymentType::Wechat->name) {
                $wechat = self::wechat()->app([
                    'out_trade_no' => $this->pay_no . str_pad((string)$this->pay_count, 2, '0', STR_PAD_LEFT),
                    'description' => $this->subject,
                    'amount' => [
                        'total' => intval(bcmul($this->amount, 100)),
                    ]
                ]);
                return $wechat->toArray();
            }
        } catch (\Exception $e) {
            $log->error($e->getMessage());
            $log->error($e->getTraceAsString());
            if ($e instanceof InvalidResponseException) {
                $log->error($e->response->toJson());
            }
        }
        return [];
    }

    public function unFreeze(string $subject)
    {
        $log = Log::channel('pay_' . strtolower($this->payment_type));
        try {
            if ($this->payment_type == PaymentType::Alipay->name) {
                if ($this->is_freeze) {
                    $alipay = self::alipay()->pay([
                        StartPlugin::class,
                        UnfreezePlugin::class,
                        FormatPayloadBizContentPlugin::class,
                        AddPayloadSignaturePlugin::class,
                        ResponseInvokeStringPlugin::class,
                        ParserPlugin::class,
                    ], [
                        'auth_no' => $this->trade_no,
                        'out_request_no' => $this->pay_no,
                        'amount' => $this->amount,
                        'remark' => $subject
                    ])->toArray();
                    if (PaymentType::Alipay->isFreezeSuccess($alipay)) {
                        $info = TradeInfo::find($this->trade_info_id);
                        $content = $info->content;
                        $content['unfreeze_result'] = $alipay;
                        $info->content = $content;
                        $info->save();
                        $this->status = OrderPayStatus::Unfreeze->value;
                        $this->save();
                    }
                    return true;
                }
            }
        } catch (\Exception $e) {
            $log->error($e->getMessage());
            $log->error($e->getTraceAsString());
            if ($e instanceof InvalidResponseException) {
                $log->error($e->response->toJson());
            }
        }
        return false;
    }

    public function getPaidAmount(): float
    {
        return round(bcsub($this->amount, $this->refund_amount, 2), 2);
    }

    public static function getPayConfig(): array
    {
        $config = [
            'alipay' => [
                'default' => [
                    // 必填-支付宝分配的 app_id
                    'app_id' => '2021004129633000',
                    // 必填-应用私钥 字符串或路径
                    // 在 https://open.alipay.com/develop/manage 《应用详情->开发设置->接口加签方式》中设置
                    'app_secret_cert' => '',
                    // 必填-应用公钥证书 路径
                    // 设置应用私钥后，即可下载得到以下3个证书
                    'app_public_cert_path' => App::getRootPath() . 'extend/Pay/alipay/appCertPublicKey_2021004129633000.crt',
                    // 必填-支付宝公钥证书 路径
                    'alipay_public_cert_path' => App::getRootPath() . 'extend/Pay/alipay/alipayCertPublicKey_RSA2.crt',
                    // 必填-支付宝根证书 路径
                    'alipay_root_cert_path' => App::getRootPath() . 'extend/Pay/alipay/alipayRootCert.crt',
                    //'return_url' => fullDomain('api') . '/index/pay/alipay',
                    'notify_url' => fullDomain('api') . '/index/pay/alipay',
                    // 选填-第三方应用授权token
                    'app_auth_token' => '',
                    // 选填-服务商模式下的服务商 id，当 mode 为 Pay::MODE_SERVICE 时使用该参数
                    'service_provider_id' => '',
                    // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SANDBOX, MODE_SERVICE
                    'mode' => Pay::MODE_NORMAL,
                ]
            ],
            'wechat' => [
                'default' => [
                    // 必填-商户号，服务商模式下为服务商商户号
                    // 可在 https://pay.weixin.qq.com/ 账户中心->商户信息 查看
                    'mch_id' => '1662527423',
                    // 选填-v2商户私钥
                    'mch_secret_key_v2' => '',
                    // 必填-v3 商户秘钥
                    // 即 API v3 密钥(32字节，形如md5值)，可在 账户中心->API安全 中设置
                    'mch_secret_key' => '',
                    // 必填-商户私钥 字符串或路径
                    // 即 API证书 PRIVATE KEY，可在 账户中心->API安全->申请API证书 里获得
                    // 文件名形如：apiclient_key.pem
                    'mch_secret_cert' => App::getRootPath() . 'extend/Pay/wechat/apiclient_key.pem',
                    // 必填-商户公钥证书路径
                    // 即 API证书 CERTIFICATE，可在 账户中心->API安全->申请API证书 里获得
                    // 文件名形如：apiclient_cert.pem
                    'mch_public_cert_path' => App::getRootPath() . 'extend/Pay/wechat/apiclient_cert.pem',
                    // 必填-微信回调url
                    // 不能有参数，如?号，空格等，否则会无法正确回调
                    'notify_url' => fullDomain('api') . '/index/pay/wechat',
                    // 选填-公众号 的 app_id
                    // 可在 mp.weixin.qq.com 设置与开发->基本配置->开发者ID(AppID) 查看
                    'mp_app_id' => 'wx59dac68054ec2ae6',
                    // 选填-小程序 的 app_id
                    //'mini_app_id' => '',
                    // 选填-app 的 app_id
                    //'app_id' => '',
                    // 选填-服务商模式下，子公众号 的 app_id
                    //'sub_mp_app_id' => '',
                    // 选填-服务商模式下，子 app 的 app_id
                    //'sub_app_id' => '',
                    // 选填-服务商模式下，子小程序 的 app_id
                    //'sub_mini_app_id' => '',
                    // 选填-服务商模式下，子商户id
                    //'sub_mch_id' => '',
                    // 选填-微信平台公钥证书路径, optional，强烈建议 php-fpm 模式下配置此参数
                    'wechat_public_cert_path' => [
                        //'45F59D4DABF31918AFCEC556D5D2C6E376675D57' => __DIR__.'/Cert/wechatPublicKey.crt',
                    ],
                    // 选填-默认为正常模式。可选为： MODE_NORMAL, MODE_SERVICE
                    'mode' => Pay::MODE_NORMAL,
                ]
            ],
            'logger' => [
                'enable' => true,
                'file' => App::getRuntimePath() . 'log/pay/pay.log',
                'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                'type' => 'daily', // optional, 可选 daily.
                'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
            ],
        ];
        $config['alipay'] = array_merge($config['alipay'], AdminConfig::getConfigValue('pay_alipay', []));
        $config['wechat'] = array_merge($config['wechat'], AdminConfig::getConfigValue('pay_wechat', []));
        return $config;
    }

    public static function alipay(): Alipay
    {
        return Pay::alipay(OrderPay::getPayConfig());
    }

    public static function wechat(): Wechat
    {
        return Pay::wechat(OrderPay::getPayConfig());
    }

    public static function generatePayNo(int $payAimType): string
    {
        while (true) {
            $orderNo = PayAimType::generatePayNo($payAimType);
            if (!self::where('pay_no', $orderNo)->value('id')) {
                return $orderNo;
            }
        }
    }
}
