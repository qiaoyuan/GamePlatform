<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\UserChannel as Model;
use app\common\annotation\Permission;
use PHPQRCode\QRcode;


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
        $param = $this->request->param();

        $info =  [
            'status' =>1,
            'created_at'=>time(),
            'title' => $param['title'],
        ];
        $obj = Model::create($info);
        if(empty($obj) ) {
            $this->error('生成失败');
        }

        QRcode::png("https://psychology.xuanzeti.top?channel_id=1045", app()->getRootPath().'public/image/qrcode/'.$obj->id.'.png');
        $obj->img_url = 'https://psychology.xuanzeti.top/image/qrcode/'.$obj->id.'.png';
        $obj->save();
        $this->success('操作成功');

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

    public function dealImg() {

        if(!is_dir(app()->getRootPath().'public/image/qrcode')) {
            mkdir(app()->getRootPath() . 'public/image/qrcode');
        }
        $res = QRcode::png("https://psychology.xuanzeti.top?channel_id=1045", app()->getRootPath().'public/image/qrcode/1045.png');
        $this->success('二维码生成成功!', app()->getRootPath().'public/image/qrcode/');

    }
    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id'],
            ['v' => 'title', 'label' => '渠道名称'],
            ['v' => 'link', 'label' => '链接'],
            ['v' => 'img_url', 'label' => '二维码', "render"=>"image"],
            ['v' => 'created_at', 'label' => '日期', 'searchType' => 'number', 'sort' => 'created_at'],
        ];
    }
}
