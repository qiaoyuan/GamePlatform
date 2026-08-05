<?php

namespace app\common\model;

/**
 * @property int $id
 * @property string $user_id 平台用户ID/账号标记
 * @property string $account_name 账号名称
 * @property int $platform 平台 1:G2G
 * @property string $active_device_token 设备活跃令牌
 * @property string $long_lived_token 长期访问令牌
 * @property string $refresh_token 刷新令牌
 * @property int $status 状态
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class GameAccount extends Base
{
    protected $table = 'game_account';
    protected $pk = 'id';
    protected $field = [
        'id',
        'user_id',
        'account_name',
        'platform',
        'active_device_token',
        'long_lived_token',
        'refresh_token',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    protected $type = [];

    const G2G_PLATFORM = 1;

    public static $PLATFORM_MAP = [
        self::G2G_PLATFORM => 'G2G',
    ];

    /**
     * 平台下拉列表
     */
    public static function getPlatformList(): array
    {
        $list = [];
        foreach (self::$PLATFORM_MAP as $value => $label) {
            $list[] = ['label' => $label, 'value' => $value];
        }
        return $list;
    }
}
