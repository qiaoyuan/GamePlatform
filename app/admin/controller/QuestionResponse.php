<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\QuestionResponse as Model;
use app\common\annotation\Permission;


class QuestionResponse extends BaseController
{
    
    #[Permission(title: '问卷反馈配置列表', isMenu: 1, parentUrl: 'article', isHideSub: 1)]
    public function index(): void
    {
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['text'])->with(['questionnaire'])
            ->selectData();
        if(input('_summary')) {
            $this->success('', [
                'count' => (new Model())->count(),
            ]);
        }

        $lists->each(function (&$item){
            $item->group_name = ($item['group_index'] == 0) ? '全部' : '阶段'.$item['group_index'];

        });

        $this->success('', [
            'list' => $lists,
        ]);
    }

    #[Permission(title: '编辑问卷反馈')]
    public function edit(): void
    {
        $this->mEdit(Model::class);
    }

    #[Permission(title: '添加问卷反馈')]
    public function add(): void
    {
        $this->mAdd(Model::class);
    }

    #[Permission(title: '删除问卷反馈')]
    public function delete(): void
    {
        $this->mDelete(Model::class);
    }

    public function get(): void
    {
        $this->success('', [
            'detail' => Model::find(input('id'))
        ]);
    }

//
//    public function select(): void
//    {
//        $this->success('', [
//            'list' => $this->tableList(Model::class)->field('title as label,id as value')->select()
//        ]);
//    }

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
            ['v'=>'group_name', 'label' => '阶段名称'],
            ['v' => 'text', 'label' => '配置内容'],
            ['v' => 'start', 'label' => '起始值', 'searchType' => 'number', 'sort' => 'start'],
            ['v' => 'lt', 'label' => '小于等于', 'searchType' => 'number', 'sort' => 'lt'],
        ];
    }
}
