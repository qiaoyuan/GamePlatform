<?php
namespace app\index\controller;

use app\index\BaseController;

use app\common\model\Questions as Model;



class Questions extends BaseController
{

    public function index()
    {
        $lists = $this->tableList(Model::class, ['sort' => 'ASC'], ['question_text'])->with(['questionnaire', 'questionOptions'])
            ->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }


}
