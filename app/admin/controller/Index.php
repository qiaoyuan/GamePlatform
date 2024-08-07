<?php
namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\AdminPermission;

class Index extends BaseController
{
    protected array $log_ignore = ['index', 'menus'];

    public function index()
    {
        $admin = $this->request->admin->toArray();
        $this->success('', [
            'admin' => $admin,
        ]);
    }

    public function menus()
    {
        $this->success('', [
            'menus' => AdminPermission::getMenuByAdmin($this->request->admin_id)
        ]);
    }

    public function clear()
    {
        $this->success('操作成功');
    }

    public function info()
    {
        $data = request()->put();
        $this->validate('admin.password', $data);
        $admin = \app\common\model\Admin::find($this->request->admin_id);
        if (!password_verify($data['opassword'], $admin->password)) {
            $this->error('原始密码错误');
        }
        $admin->save(['password' => setPassWord(trim($data['password'])), 'is_password' => 1]);
        $this->success('修改成功');
    }
}
