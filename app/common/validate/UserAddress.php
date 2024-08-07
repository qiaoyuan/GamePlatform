<?php

namespace app\common\validate;

class UserAddress extends Base
{
    protected $rule = [
        'province_id|省' => ['require'],
        'city_id|市' => ['require'],
        'region_id|区' => ['require'],
        'detail|详细地址' => ['require'],
        'is_default|默认地址' => ['require'],
        'consignee|收件人' => ['require'],
        'phone|电话' => ['require', 'phone'],
        'id_card_no|身份证' => ['require', 'idCard'],
    ];

    protected $message = [];

    protected $scene = [
        'add' => ['province_id', 'city_id', 'region_id', 'detail', 'is_default', 'consignee', 'phone', 'id_card_no'],
        'edit' => [
            'province_id',
            'city_id',
            'region_id',
            'detail',
            'is_default',
            'consignee',
            'phone',
            'id_card_no',
            'id'
        ],
    ];
}
