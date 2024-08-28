<?php
declare (strict_types = 1);

namespace app\index;

use think\Response;

class BaseController extends \app\common\BaseController
{
    public function getUid()
    {
        return intval($this->request->uid);
//        $uid = 999;
//        return $uid;
    }

    public function getOpenId()
    {
        return $this->request->open_id;
//        $uid = 999;
//        return $uid;
    }
    protected function getResponseType(): string
    {
        return 'json';
    }
}
