<?php
namespace app\index\controller;

use app\index\BaseController;

class Home extends BaseController
{

    public function index()
    {
//        $icp = mData('siteConfig')['icp'] ?? '';
//        include template('index');
        echo  app()->getRootPath()."public";
    }
    public function getList() {
        echo 'getlist';
    }
}
