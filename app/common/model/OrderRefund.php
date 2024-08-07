<?php

namespace app\common\model;

use app\common\enum\OrderPayStatus;
use app\common\enum\OrderRefundStatus;
use app\common\enum\PaymentType;
use app\common\enum\RefundAimType;
use app\common\traits\UserWith;
use think\Collection;
use think\facade\Log;
use think\model\relation\BelongsTo;

/**
 * @property int $id
 * @property int $trade_info_id
 * @property int $order_pay_id
 * @property string $refund_no 退款单编号
 * @property int $status 退款状态
 * @property float $amount 金额
 * @property string $created_at
 * @property string $refund_at 退款时间
 * @property string $payment_type 退款方式（支付宝，微信，余额）
 * @property int $user_id
 * @property string $subject 退款标题
 * @property string $trade_no 流水号
 * @property int $refund_aim_type 退款类型（下单，日本国内运费，验货拍照，合箱发货，国内运费）
 * @property int $verify_admin_id 审核人
 * @property int $pay_admin_id 退款人
 * @property string $verify_at 审核时间
 * @property OrderPay $orderPay
 */
class OrderRefund extends Base
{
    use UserWith;
    protected $table = 'order_refund';
    protected $pk = 'id';
    protected $updateTime = false;
    protected $field = [
        'id',
        'trade_info_id',
        'order_pay_id',
        'refund_no',
        'status',
        'amount',
        'created_at',
        'refund_at',
        'payment_type',
        'user_id',
        'subject',
        'trade_no',
        'refund_aim_type',
        'verify_admin_id',
        'pay_admin_id',
        'verify_at',
    ];
    protected $type = [
        'amount' => 'float',
    ];

    public function orderPay(): BelongsTo
    {
        return $this->belongsTo(OrderPay::class, 'order_pay_id', 'id');
    }

    /**
     * 快捷退款，原路退回
     * @param OrderPay $orderPay
     * @param int $refundAimType
     * @param string $subject
     * @param int $adminId
     * @param float $amount
     * @return bool
     */
    public static function refund(OrderPay $orderPay, int $refundAimType, string $subject, int $adminId = 0, float $amount = 0): bool
    {
        $orderRefund = self::create([
            'trade_info_id' => 0,
            'order_pay_id' => $orderPay->id,
            'refund_no' => self::generateRefundNo($refundAimType),
            'status' => OrderRefundStatus::Pending->value,
            'amount' => $amount ?: $orderPay->getPaidAmount(),
            'refund_at' => null,
            'payment_type' => $orderPay->payment_type,
            'user_id' => $orderPay->user_id,
            'subject' => $subject,
            'trade_no' => '',
            'refund_aim_type' => $refundAimType,
            'verify_admin_id' => $adminId ?: request()->admin_id,
            'pay_admin_id' => 0,
            'verify_at' => dateNow(),
        ]);
        return $orderRefund->refundPay();
    }

    public function refundPay(): bool
    {
        $orderPay = $this->orderPay;
        if ($orderPay->status != OrderPayStatus::Paid->value && $orderPay->status != OrderPayStatus::RefundPart->value) {
            $this->error = '无效的退款单';
            return false;
        }
        if (bccomp($orderPay->getPaidAmount(), $this->amount) < 0) {
            $this->error = '退款金额不能大于付款金额';
            return false;
        }
        if($this->payment_type != PaymentType::Amount->name && $this->payment_type != $orderPay->payment_type) {
            $this->error = '支付宝和微信支付只支持原路退回';
            return false;
        }
        $log = Log::channel('pay_' . strtolower($this->payment_type));
        switch ($this->payment_type) {
            case PaymentType::Alipay->name:
                $paymentType = PaymentType::Alipay;
                $result = OrderPay::alipay()->refund([
                    'trade_no' => $orderPay->trade_no,
                    'refund_amount' => $this->amount,
                    'out_request_no' => $this->refund_no
                ]);
                break;
            case PaymentType::Wechat->name:
                $paymentType = PaymentType::Wechat;
                $result = OrderPay::wechat()->refund([
                    'transaction_id' => $orderPay->trade_no,
                    'out_refund_no' => $this->refund_no,
                    'amount' => [
                        'total' => intval(bcmul($orderPay->amount, 100)),
                        'refund' => intval(bcmul($this->amount, 100)),
                        'currency' => 'CNY'
                    ],
                    '_action' => 'native'
                ]);
                break;
            case PaymentType::Amount->name:
                $paymentType = PaymentType::Amount;
                $user = User::find($this->user_id);
                $user->amount = bcadd($user->amount, $this->amount, 2);
                $user->save();
                $result = new Collection();
                break;
            default:
                $this->error = '无效的退款方式';
                return false;
        }

        $log->log('refund', $result->toJson(320));
        $result = $result->toArray();
        if(!$paymentType->isRefundSuccess($result)) {
            $this->error = '退款失败';
            return false;
        }
        $info = TradeInfo::create([
            'content' => $result,
            'type' => 'Refund',
            'trade_no' => $paymentType->getRefundTradeNo($result),
            'payment_type' => $orderPay->payment_type,
        ]);
        $orderPay->refund_amount = bcadd($this->amount, $orderPay->refund_amount, 2);
        if (bccomp($orderPay->amount, $orderPay->refund_amount) > 0) {
            $orderPay->status = OrderPayStatus::RefundPart->value;
        } else {
            $orderPay->status = OrderPayStatus::RefundAll->value;
        }
        $orderPay->save();
        $this->trade_no = $paymentType->getRefundTradeNo($result);
        $this->trade_info_id = $info->id;
        $this->status = 1;
        $this->refund_at = dateNow();
        $this->save();
        $this->refundSuccess();
        return true;
    }

    public function refundSuccess(): void
    {
    }

    public static function generateRefundNo(int $payAimType): string
    {
        while (true) {
            $orderNo = RefundAimType::generateRefundNo($payAimType);
            if (!self::where('refund_no', $orderNo)->value('id')) {
                return $orderNo;
            }
        }
    }
}
