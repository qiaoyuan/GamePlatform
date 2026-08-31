<?php

namespace app\common\model;

/**
 * 游戏账号第三方 API 调用日志（G2G 等平台的令牌刷新/改价等操作留痕）
 *
 * @property int $id
 * @property int $game_account_id 关联游戏账号id
 * @property int $game_product_id 关联产品id(非该商品操作则为0)
 * @property string $type 调用类型 refresh_token/update_price/sync_offer
 * @property string $request_url 请求地址
 * @property string $request_data 请求参数(敏感字段已脱敏)
 * @property string $response_data 响应内容
 * @property int $status 0:失败 1:成功
 * @property string $error_msg 错误信息
 * @property int $duration_ms 耗时(毫秒)
 * @property int $admin_id 操作管理员
 * @property string $created_at
 */
class GameAccountApiLog extends Base
{
    protected $updateTime = false;
    protected $table = 'game_account_api_log';
    protected $pk = 'id';
    protected $field = [
        'id',
        'game_account_id',
        'game_product_id',
        'type',
        'request_url',
        'request_data',
        'response_data',
        'status',
        'error_msg',
        'duration_ms',
        'admin_id',
        'created_at',
    ];
    protected $type = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    const TYPE_REFRESH_TOKEN = 'refresh_token';
    const TYPE_UPDATE_PRICE = 'update_price';
    const TYPE_SYNC_OFFER = 'sync_offer';

    const STATUS_FAIL = 0;
    const STATUS_SUCCESS = 1;

    /**
     * 记录一条调用日志
     */
    public static function record(array $data): void
    {
        self::create(array_merge([
            'game_account_id' => 0,
            'game_product_id' => 0,
            'admin_id' => request()->admin_id ?? 0,
        ], $data));
    }
}
