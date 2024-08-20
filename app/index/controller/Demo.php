<?php
namespace app\index\controller;

use app\index\BaseController;

class Demo extends BaseController
{

    public function index()
    {
//        $icp = mData('siteConfig')['icp'] ?? '';
//        include template('index');
        echo "demo";
    }
}
