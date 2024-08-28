<?php

namespace app\common\model;

/**
 * @property int $id
 * @property string $created_at 创建时间
 * @property int $status 1：正常订单
 * @property string $input_data 请求订单数据
 * @property string $updated_at
 */
class QuestionnairesOrderCallback extends Base
{

    protected $autoWriteTimestamp = true;


    protected $table = 'questionnaires_order_callback';
    protected $pk = 'id';
    protected $field = [
        'id',
        'created_at',
        'status',
        'input_data',
        'updated_at',
    ];
    protected $type = [];
}
