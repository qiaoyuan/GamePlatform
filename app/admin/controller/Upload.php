<?php
namespace app\admin\controller;

use app\admin\BaseController;

class Upload extends BaseController
{
    use \app\common\traits\Upload;

    public function index()
    {
        $this->uploadHandle($this->request->admin_id);
    }

    public function create()
    {
        $this->success('', [
            'upload' => config('upload.admin')
        ]);
    }
}
