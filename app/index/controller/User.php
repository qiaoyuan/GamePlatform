<?php
namespace app\index\controller;

use app\index\BaseController;

class User extends BaseController
{

    public function register()
    {

        $this->success('注册成功', []);
    }

    //用户数据
    public  function info() {

        $this->success('', []);
    }
}
