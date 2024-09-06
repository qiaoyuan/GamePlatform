<?php

namespace app\common\model;

use think\model\relation\BelongsTo;

/**
 * @property int $id
 * @property int $questionnaire_id 问卷id
 * @property int $order_id 订单号
 * @property int $uid 用户
 * @property int $status 1：正常订单
 * @property string $input_data 请求订单数据
 * @property float $price 问卷价格
 * @property int $pay_status 0：未支付，1：支付
 * @property string $pay_extent 支付请求数据
 * @property string $created_at
 * @property string $updated_at
 * @property int $pay_type 1 微信支付， 2、抖音-支付3、抖音-微信4、抖音-支付宝
 */
class QuestionnairesOrder extends Base
{

    protected $table = 'questionnaires_order';
    protected $pk = 'id';
    protected $field = [
        'id',
        'questionnaire_id',
        'order_id',
        'uid',
        'status',
        'input_data',
        'price',
        'pay_status',
        'pay_extent',
        'created_at',
        'updated_at',
        'pay_type',
    ];
    protected $type = [
        'price' => 'float',
    ];

    const PAY_UNPAID_STATUS = 0;
    const PAY_PAID_STATUS = 1;

    public static $PAY_MAP = [
        self::PAY_UNPAID_STATUS => '未支付',
        self::PAY_PAID_STATUS => '已支付',
    ];

    const NONE_STATUS = 1;
    const DEL_STATUS = 0;
    public static $statusMap = [
        self::NONE_STATUS => '正常',
        self::DEL_STATUS => '删除',
    ];

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaires::class, 'questionnaire_id', 'id');
    }

    public static function getPayStatusList()
    {
        return [
            ['label' => self::$PAY_MAP[self::PAY_UNPAID_STATUS], 'value' => self::PAY_UNPAID_STATUS],
            ['label' => self::$PAY_MAP[self::PAY_PAID_STATUS], 'value' => self::PAY_PAID_STATUS],
        ];
    }

    public static function getPayTypeList()
    {
        $arr = [];
        foreach (self::$PAY_TYPE_MAP as $k => $v) {
            $arr[] = ['label' => $v, 'value' => $k];
        }
        return $arr;
    }

    public static function getStatusList()
    {
        return [
            ['label' => '正常', 'value' => 1],
            ['label' => '删除', 'value' => 0],
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }

    const PAY_TYPE = 0;
    const PAY_TYPE_WX = 1;
    const PAY_TYPE_DY_PAY = 2;
    const PAY_TYPE_DY_WX = 3;
    const PAY_TYPE_DY_ALIPAY = 4;

    public static $PAY_TYPE_MAP = [
        self::PAY_TYPE => '未支付',
        self::PAY_TYPE_WX => '微信支付',
        self::PAY_TYPE_DY_PAY => '抖音支付',
        self::PAY_TYPE_DY_WX => '抖音微信',
        self::PAY_TYPE_DY_ALIPAY => '抖音支付宝',
    ];

}