<?php

namespace app\index\controller;

use app\index\BaseController;

use app\common\model\Questions as Model;


class Questions extends BaseController
{

    public function list()
    {
        $param = $this->request->param();
        if (empty($param['questionnaire_id'])) {
            $this->error('参数错误');
        }

        $lists = Model::order(['sort' => 'ASC'])->with(['questionOptions' => function ($query) {
            $query->order(['sort' => 'ASC']);
        }])->select();
        $lists->each(function ($item) {
            $item->options = $item->questionOptions;
            unset($item->questionOptions);
        });
        $this->success('', [
            'total' => $lists->count(),
            'list' => $lists,
        ]);
    }


}
