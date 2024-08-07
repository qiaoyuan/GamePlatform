<?php
namespace app\admin\controller;

use app\common\BaseController;
use app\common\model\Admin;
use think\Response;

class Account extends BaseController
{
    public function index()
    {
        echo '1';
        return;
    }

    public function login()
    {
        $data = $this->request->post();
        $this->validate('admin.login', $data);
        $model = (new Admin());
        $admin = $model->login($data['username'], $data['password']);
        if (!$admin) {
            $this->error($model->getError());
        }
        $this->success('登录成功', ['token' => Admin::getToken($admin->id, $admin->nickname)]);
    }

    public function logout()
    {

    }

    public function captcha(): Response
    {
        return Captcha::create();
    }

    public function getResponseType(): string
    {
        return 'json';
    }
}
