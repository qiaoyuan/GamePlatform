<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 游戏账号
 *
 * @property int    $id
 * @property string $user_id              用户ID
 * @property string $account_name         账号名称
 * @property int    $platform             平台
 * @property string $active_device_token  设备令牌
 * @property string $long_lived_token     长期令牌
 * @property string $refresh_token        刷新令牌
 * @property int    $status               状态 0-停用 1-启用
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class GameAccount extends Base
{
    protected $table = 'game_account';
    protected $pk    = 'id';

    /** @var string[] 字段列表 */
    protected $field = ['id', 'user_id', 'account_name', 'platform', 'active_device_token', 'long_lived_token', 'refresh_token', 'status', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @var array<string, string> 字段类型
     */
    protected $type = [
        'id'                  => 'int',
        'user_id'             => 'string',
        'account_name'        => 'string',
        'platform'            => 'int',
        'active_device_token' => 'string',
        'long_lived_token'    => 'string',
        'refresh_token'       => 'string',
        'status'              => 'int',
        'created_at'          => 'string',
        'updated_at'          => 'string',
        'deleted_at'          => 'string',
    ];

    // ==================== 枚举 ====================

    /** 状态：停用 */
    const STATUS_OFF = 0;
    /** 状态：启用 */
    const STATUS_ON  = 1;

    /** @var array<int, string> 状态映射 */
    public static $STATUS_MAP = [
        self::STATUS_OFF => '停用',
        self::STATUS_ON  => '启用',
    ];

    // ==================== 平台枚举 ====================

    /** 平台：G2G */
    const PLATFORM_G2G = 1;

    /**
     * 兼容旧代码中的常量名称：历史上该值曾误命名为 Facebook，实际业务平台为 G2G。
     * @deprecated 请使用 PLATFORM_G2G
     */
    const PLATFORM_FACEBOOK = self::PLATFORM_G2G;

    /** @var array<int, string> 平台映射 */
    public static $PLATFORM_MAP = [
        self::PLATFORM_G2G => 'G2G',
    ];

    /**
     * 平台枚举列表
     */
    public static function getPlatformList(): array
    {
        return [
            ['value' => self::PLATFORM_G2G, 'label' => 'G2G'],
        ];
    }

    /**
     * 状态枚举列表
     */
    public static function getStatusList(): array
    {
        return [
            ['value' => self::STATUS_OFF, 'label' => '停用'],
            ['value' => self::STATUS_ON,  'label' => '启用'],
        ];
    }
}
