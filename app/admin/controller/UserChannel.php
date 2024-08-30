<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\UserChannel as Model;
use app\common\annotation\Permission;


class UserChannel extends BaseController
{

    #[Permission(title: '渠道列表', isMenu: 1, parentUrl: 'article', isHideSub: 1)]
    public function index(): void
    {
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['title'])
            ->selectData();

        if (!is_numeric($lists)) {
            $lists->each(function ($item) {
                $item->link = "https://psychology.xuanzeti.top?channel_id=" . $item->id;
            });
        }
        $this->success('', [
            'list' => $lists,
        ]);

    }

    #[Permission(title: '修改渠道')]
    public function edit(): void
    {
        $this->mEdit(Model::class);
    }

    #[Permission(title: '添加渠道')]
    public function add(): void
    {
        $this->mAdd(Model::class);
    }

    #[Permission(title: '删除渠道')]
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

    #[Permission(title: '修改状态')]
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

    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id'],
            ['v' => 'title', 'label' => '渠道名称'],
            ['v' => 'link', 'label' => '链接'],
//            ['v' => 'img_url', 'label' => '二维码', "render"=>"image"],
            ['v' => 'status', 'label' => '是否启用', 'render' => 'status', 'sort' => 'status'],
            ['v' => 'created_at', 'label' => '日期', 'searchType' => 'number', 'sort' => 'created_at'],
        ];
    }
}
