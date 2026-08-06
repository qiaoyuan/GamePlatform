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
        // price 不参与 edit 场景校验：改价需同步 G2G 平台，只能走 GameProduct::updatePrice() 接口，
        // 常规 edit 接口会用 except 剔除 price 字段（见 GameProduct::edit()），故此处不能要求其必填。
        'add' => ['game_account_id', 'product_id', 'title', 'platform', 'price', 'stock', 'currency', 'status'],
        'edit' => ['game_account_id', 'product_id', 'title', 'platform', 'stock', 'currency', 'status', 'id'],
    ];
}
