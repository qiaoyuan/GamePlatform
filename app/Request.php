<?php
namespace app;

// 应用请求对象类
use app\common\model\Admin;
use app\common\model\User;

/**
 * @property string $nickname
 * @property int $admin_id
 * @property int $user_id
 * @property Admin $admin
 * @property User $user
 */
class Request extends \think\Request
{
    /**
     * 获取中间传递数据的值
     * @access public
     * @param  string $name 名称
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name === 'admin') {
            $admin = parent::__get($name);
            if (!$admin) {
                $adminId = $this->middleware('admin_id');
                if ($adminId) {
                    $admin = Admin::findOrEmpty($adminId)->hidden(['password']);
                    if (!$admin->isEmpty()) {
                        $this->__set($name, $admin);
                    } else {
                        $this->__set('admin_id', 0);
                    }
                }
            } else {
                return $admin;
            }
        }
        if ($name === 'user') {
            $admin = parent::__get($name);
            if (!$admin) {
                $adminId = $this->middleware('user_id');
                if ($adminId) {
                    $admin = User::findOrEmpty($adminId)->hidden(['password']);
                    if (!$admin->isEmpty()) {
                        $this->__set($name, $admin);
                    } else {
                        $this->__set('user_id', 0);
                        cookie('user_id', null);
                    }
                }
            } else {
                return $admin;
            }
        }
        return parent::__get($name);
    }
}
