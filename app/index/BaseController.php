<?php
declare (strict_types = 1);

namespace app\index;

use think\Response;

class BaseController extends \app\common\BaseController
{
    protected function getResponseType(): string
    {
        return 'json';
    }
}
