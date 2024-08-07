<?php

namespace test\order;

use test\BaseCase;

class IndexTest extends BaseCase
{

    protected array $user = [
        'user_id' => 4,
        'nickname' => '测试'
    ];

    public static function setUpBeforeClass(): void
    {

    }

    public static function tearDownAfterClass(): void
    {
    }

    public function testAdd()
    {
        $r = $this->post('/user/order/add', [
            'product_id' => 1,
            'date' => date('Y-m-d'),
            'passengers' => [
                [
                    'phone' => '13888888888',
                    'id_card' => '510703201603222410',
                    'name' => '测试1',
                    'is_disable' => 1,
                    'disable_id_card' => '',
                    'is_chair' => 1,
                    'is_bus' => 0,
                    'is_companion' => 0,
                    'remark' => '',
                ],
                [
                    'phone' => '13888888888',
                    'id_card' => '510703202202011510',
                    'name' => '测试2',
                    'is_disable' => 1,
                    'disable_id_card' => '51070320220201151011',
                    'is_chair' => 1,
                    'is_bus' => 0,
                    'is_companion' => 0,
                    'remark' => '',
                ],
            ]
        ]);
        $this->assertApiSuccess($r ?? []);
    }
}