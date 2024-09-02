<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\QuestionAnswers as Model;
use app\common\annotation\Permission;


class QuestionAnswers extends BaseController
{
    
    #[Permission(title: '回答列表', isMenu: 1, parentUrl: 'article', isHideSub: 1)]
    public function index(): void
    {

        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['answer_text'])
            ->with(['questions', 'user', 'questionnaire', 'questionOptions'])
            ->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }

    #[Permission(title: '回答编辑')]
    public function edit(): void
    {
        $this->mEdit(Model::class);
    }

    #[Permission(title: '用户回答')]
    public function add(): void
    {
        $this->mAdd(Model::class);
    }

    #[Permission(title: '删除回答')]
    public function delete(): void
    {
        $this->mDelete(Model::class);
    }

    public function get(): void
    {
        $obj = Model::find(input('id'));
        $this->success('', [
            'info' => $obj,
            'option' => $obj->questionOptions,
            'questionnaire' => $obj->questionnaire,
            'questions' => $obj->questions,
            'user' => $obj->user,
        ]);
    }


    public function columns(): array
    {
        return [
            [
                'v' => 'questions.title',
                'label' => '问题名称',
                'search' => 'question_id',
                'sort' => 'question_id',
                'searchType' => 'multiple',
                'searchList' => '/questions/select',
            ],
            ['v' => 'answer_text', 'label' => '用户回答'],
            ['v' => 'created_at', 'label' => '日期', 'searchType' => 'number', 'sort' => 'created_at'],
            [
                'v' => 'option.title',
                'label' => '选项名称',
                'sort' => 'option_id',
                'searchType' => 'multiple',
                'searchList' => '/questionsOptions/select',
            ],
            ['v' => 'user.nickname', 'label' => '用户', 'search'=>'uid', 'searchType' => 'number', 'sort' => 'uid'],
            [
                'v' => 'questionnaire.title',
                'label' => '问卷名称',
                'sort' => 'questionnaire_id',
                'searchType' => 'multiple',
                'searchList' => '/questionnaires/select',
            ],
        ];
    }

    public function  getUserAnswerScore()
    {
        $param = $this->request->param();
        if(empty($param['uid']) || empty($param['questionnaire_id'])) {
           $this->error("参数缺少：uid，问卷id");
        }

        $this->success('', ['score'=>(new Model())->getUserAnswerScore($param['questionnaire_id'], $param['uid'])]);
    }
}
