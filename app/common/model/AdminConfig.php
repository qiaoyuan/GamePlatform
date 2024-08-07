<?php

namespace app\common\model;

/**
 * @property int $id 配置ID
 * @property string $name 配置名称
 * @property string $title 配置标题
 * @property int $group 配置分组
 * @property string $remark 配置说明
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property int $status 状态
 * @property string $value 配置值
 * @property int $sort 排序
 * @property int $type
 */
class AdminConfig extends Base
{
    const TYPE_VALUE = 0;
    const TYPE_ARRAY = 1;

    const SWITCH_KEY = 'ext_switch';

    const TRANS_KEY = 'trans_link_config';

    const ACCOUNT_KEY = 'lm_account';

    protected $table = 'admin_config';
    protected $pk = 'id';
    protected $field = [
        'id',
        'name',
        'title',
        'group',
        'remark',
        'created_at',
        'updated_at',
        'status',
        'value',
        'sort',
        'type',
    ];
    protected $type = [];

    public function setValueAttr($value, $data)
    {
        if ($data['type'] == self::TYPE_ARRAY) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return $value;
    }

    public function getValueAttr($value, $data)
    {
        if ($data['type'] == self::TYPE_ARRAY) {
            return json_decode($value, true);
        }
        return $value;
    }

    public static function getConfigValue($name, $default = null)
    {
        $config = fastCache('admin_config', function () {
            $config = (new AdminConfig())->field('name,value,type')->select()->toArray();
            return array_column($config, 'value', 'name');
        });
        return $config[$name] ?? $default;
    }

    public static function getExtConfigKey($default, $m)
    {
        if ($m) {
            $default = $default . '_' . $m;
        }
        return $default;
    }
}
