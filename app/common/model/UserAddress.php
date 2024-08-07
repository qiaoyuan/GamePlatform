<?php

namespace app\common\model;

use app\common\traits\UserWith;

/**
 * @property int $id
 * @property int $user_id
 * @property int $province_id 省
 * @property int $city_id 市
 * @property int $region_id 区
 * @property string $detail 详细地址
 * @property string $created_at
 * @property string $updated_at
 * @property int $is_default 默认地址
 * @property string $consignee 收件人
 * @property string $phone 电话
 * @property string $id_card_no 身份证
 */
class UserAddress extends Base
{
    use UserWith;

    protected $table = 'user_address';
    protected $pk = 'id';
    protected $field = [
        'id',
        'user_id',
        'province_id',
        'city_id',
        'region_id',
        'detail',
        'created_at',
        'updated_at',
        'is_default',
        'consignee',
        'phone',
        'id_card_no',
    ];
    protected $type = [];
}
