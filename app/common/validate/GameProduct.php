<?php

namespace app\common\validate;

class GameProduct extends Base
{
    protected $rule = [
        'game_account_id|关联账号' => ['require'],
        'product_id|产品ID' => ['require'],
        'title|产品名称' => ['require'],
        'platform|平台' => ['require'],
        'price|价格' => ['require', 'float'],
        'stock|库存' => ['require', 'integer'],
        'currency|货币' => [],
        'status|状态' => ['require'],
    ];

    protected $message = [];

    protected $scene = [
        'add' => ['game_account_id', 'product_id', 'title', 'platform', 'price', 'stock', 'currency', 'status'],
        'edit' => ['game_account_id', 'product_id', 'title', 'platform', 'price', 'stock', 'currency', 'status', 'id'],
    ];
}
