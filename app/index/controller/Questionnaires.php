<?php
namespace app\index\controller;

use app\index\BaseController;

use app\common\model\Questionnaires as Model;



class Questionnaires extends BaseController
{

    public function index()
    {
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['title', 'description'])->with(['articleCategorys'])
            ->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }




}
