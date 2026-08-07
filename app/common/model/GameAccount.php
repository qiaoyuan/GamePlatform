<?php
declare(strict_types=1);

namespace app\common\model;

/**
 * 游戏账号
 *
 * @property int    $id
 * @property string $account  账号
 * @property string $password 密码
 * @property int    $status   状态 0-停用 1-启用
 * @property string $remark   备注
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 */
class GameAccount extends Base
{
    protected $table = 'game_account';
    protected $pk    = 'id';

    /** @var string[] 字段列表 */
    protected $field = ['id', 'account', 'password', 'status', 'remark', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * @var array<string, string> 字段类型
     */
    protected $type = [
        'id'         => 'int',
        'account'    => 'string',
        'password'   => 'string',
        'status'     => 'int',
        'remark'     => 'string',
        'created_at' => 'string',
        'updated_at' => 'string',
        'deleted_at' => 'string',
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
