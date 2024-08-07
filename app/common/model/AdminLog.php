<?php

namespace app\common\model;

use think\facade\Env;
use think\helper\Str;

/**
 * @property int $id
 * @property string $api 接口
 * @property string $title 操作
 * @property string $param 参数
 * @property int $admin_id 人员
 * @property string $created_at
 */
class AdminLog extends Base
{
    protected $updateTime = false;
    protected $table = 'admin_log';
    protected $field = [
        'id',
        'api',
        'title',
        'param',
        'admin_id',
        'created_at',
    ];
    protected $pk    = 'id';

    protected $type  = ['id' => 'integer', 'param' => 'array'];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id')
            ->field(Admin::SHOW_FIELDS);
    }

    public static function log($ignore = []): bool
    {
        if (Env::get('APP_STATUS')) {
            //return true;
        }
        $controller = request()->controller();
        $action = request()->action();
        $param = request()->param();
        if ($ignore && in_array($action, $ignore)) {
            return false;
        }
        self::create([
            'api' => Str::camel($controller) . '/' . $action,
            'param' => $param ?: [],
            'admin_id' => request()->admin_id,
        ]);
        return true;
    }
}
