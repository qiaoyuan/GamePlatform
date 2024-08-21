<?php

namespace app\index\controller;

use app\index\BaseController;

use app\common\model\Questionnaires as Model;
use app\common\model\ArticleCategory as AtricleCategory;


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

    public function catList()
    {
        $where = [
            'status' => 1,
        ];
        $list = (new AtricleCategory())->getList($where, ['id', 'title', 'sort'], ['sort' => 'ASC']);

        $this->success('', [
            'list' => $list,
        ]);

    }

    public function list()
    {
        $param = $this->request->param();
        $where [] = ['status', '=', 1];

        if (!empty($param['article_category_id'])) {
            $where [] = ['article_category_id', '=', $param['article_category_id']];
        }
        if (!empty($param['title'])) {
            $where[] = ['title', 'like', '%' . $param['title'] . '%'];
        }

        $list = $this->tableList(Model::class, ['sort' => 'ASC'],)
            ->field(['id', 'title', 'description', 'price', 'img_url', 'article_category_id'])
            ->where($where)
            ->selectData();

        $this->success('', [
            'list' => $list,
        ]);
    }


    public function get()
    {
        $param = $this->request->param();
        if (empty($param['id'])) {
            $this->error('参数错误');
        }

        $info = Model::find($param['id']);
        $this->success('', [
            'info' => $info,
        ]);
    }

}
