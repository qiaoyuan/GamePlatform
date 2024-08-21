<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\Questions as Model;
use app\common\annotation\Permission;
use app\common\model\QuestionsOptions;


class Questions extends BaseController
{

    #[Permission(title: '问题表', isMenu: 1, parentUrl: 'article', isHideSub: 1)]
    public function index(): void
    {
        $lists = $this->tableList(Model::class, ['sort' => 'ASC'], ['question_text'])->with(['questionnaire', 'questionOptions'])
            ->selectData();
            $this->success('', [
            'list' => $lists,
        ]);
    }

    #[Permission(title: '编辑问题')]
    public function edit(): void
    {
        $param = $this->request->param();

        $err = transaction(function () use ($param) {

            Model::update($param, ['id' => $param['id']]);
            QuestionsOptions::where(['question_id' => $param['id']])->delete();

            $time = time();
            $options = $param['options'];
            foreach ($options as &$item) {
                $item['created_at'] = $time;
                $item['question_id'] = $param['id'];
            }
            QuestionsOptions::insertAll($options);

        }, $this);

        if (empty($err)) {
            $this->success("修改成功");
        } else {
            $this->success("修改失败" . $err);
        }


    }

    #[Permission(title: '新增问题')]
    public function add(): void
    {
        $param = $this->request->param();
        $time = time();
        $question = [
            'questionnaire_id' => $param['questionnaire_id'],
            'title' => $param['title'],
            'sort' => $param['sort'],
            'question_type' => 1,
            'created_at' => $time,
        ];
//        $options = [
//            [
//                'title' => '选项1',
//                'sort' => 1,
//                'score' => 1,
//            ],
//
//            [
//                'title' => '选项2',
//                'sort' => 2,
//                'score' => 2,
//            ],
//
//            [
//                'title' => '选项3',
//                'sort' => 3,
//                'score' => 3,
//            ],
//
//            [
//                'title' => '选项3',
//                'sort' => 4,
//                'score' => 4,
//            ],
//        ];
        $options = $param['options'];


        $err = transaction(function () use ($question, $options, $time) {
            $question = Model::Create($question);
            foreach ($options as &$item) {
                $item['created_at'] = $time;
                $item['question_id'] = $question->id;
            }
            QuestionsOptions::insertAll($options);
        }, $this);
        if (empty($err)) {
            $this->success("添加成功");
        } else {
            $this->success("添加失败");
        }

    }

    #[Permission(title: '删除问题')]
    public function delete(): void
    {
        $this->mDelete(Model::class);
    }

    public function get(): void
    {
        $obj = Model::find(input('id'));
        $this->success('', [
            'info' => $obj,
            'questionnaire' => $obj->questionnaire,
            'options' => $obj->questionOptions,
        ]);
    }


    #[Permission(title: '修改问题状态')]
    public function status(): void
    {
        $status = input('status', 0);
        Model::update(['status' => $status], ['id' => $this->getInputPk()]);
        $this->success('修改成功', ['status' => $status]);
    }

    public function select(): void
    {
        $this->success('', [
            'list' => $this->tableList(Model::class)->field('title as label,id as value')->select()
        ]);
    }

    public function create()
    {
        $this->success('', [
            'questionType' => [],
        ]);
    }

    public function type()
    {
        return [
            ['v' => '1', 'label' => '普通'],
        ];
    }
    public function getOptions()
    {
        $param = $this->request->param();
//        QuestionsOptions::where("qu_id")
    }

    public function columns(): array
    {
        return [
            [
                'v' => 'questionnaire.title',
                'label' => '问卷名称',
                'search' => 'questionnaire_id',
                'sort' => 'questionnaire_id',
                'searchType' => 'multiple',
                'searchList' => '/questionnaires/select',
            ],
            ['v' => 'title', 'label' => '问题'],
//            [
//                'v' => 'question_type',
//                'label' => '问题类型',
//                'sort' => 'question_type',
//                'searchType' => 'multiple',
//                'searchList' => Model::getQuestionType(),
//            ],
            ['v' => 'sort', 'label' => '排序', 'searchType' => 'number', 'sort' => 'sort'],
            ['v' => 'created_at', 'label' => '日期', 'search' => 'created_at', 'searchType' => 'date_range', 'sort' => 'created_at'],

        ];
    }
}
