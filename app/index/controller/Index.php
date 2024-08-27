<?php
namespace app\index\controller;

use app\index\BaseController;

class Index extends BaseController
{

    public function index()
    {
        $icp = mData('siteConfig')['icp'] ?? '';
        include template('index');
    }
}
