<?php

namespace app\common\model;

/**
 * @property int $id
 * @property int $questionnaire_id 问卷id
 * @property int $order_id 订单号
 * @property int $uid 用户
 * @property string $created_at 创建时间
 * @property string $updated_at 修改时间
 * @property int $status 1：正常订单
 * @property string $input_data 请求订单数据
 * @property float $price 问卷价格
 * @property int $pay_status 0：未支付，1：支付
 * @property string $pay_extent 支付请求数据
 */
class QuestionnairesOrder extends Base
{
    protected $autoWriteTimestamp = true;

    protected $table = 'questionnaires_order';
    protected $pk = 'id';
    protected $field = [
        'id',
        'questionnaire_id',
        'order_id',
        'uid',
        'created_at',
        'updated_at',
        'status',
        'input_data',
        'price',
        'pay_status',
        'pay_extent',
    ];
    protected $type = [
        'price' => 'float',
    ];

    const PAY_UNPAID_STATUS = 0;
    const PAY_PAID_STATUS = 1;
    const pay_status = [
        self::PAY_UNPAID_STATUS => '未支付',
        self::PAY_PAID_STATUS => '已支付',
    ];
}
