<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\UserChannel as Model;
use app\common\annotation\Permission;
use PHPQRCode\QRcode;


class UserChannel extends BaseController
{

    #[Permission(title: '渠道列表', isMenu: 1, parentUrl: 'channel', isHideSub: 1)]
    public function index(): void
    {
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['title'])
            ->selectData();

        if (!is_numeric($lists)) {
            $lists->each(function ($item) {
                $item->link = fullDomain('api').'?channel_id=' . $item->id;
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

        QRcode::png(fullDomain('api').'?channel_id='.$obj->id, app()->getRootPath().'public/image/qrcode/'.$obj->id.'.png');
        $obj->img_url = fullDomain('api').'/image/qrcode/'.$obj->id.'.png';
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

        $param = $this->request->param();
        if(empty($param['id'])) {
            $this->error("参数缺少id");
        }

//        $obj->img_url = 'https://psychology.xuanzeti.top/image/qrcode/'.$obj->id.'.png';


        if(!is_dir(app()->getRootPath().'public/image/qrcode')) {
            mkdir(app()->getRootPath() . 'public/image/qrcode');
        }

        $res = QRcode::png(fullDomain('api').'?channel_id='.$param['id'], app()->getRootPath().'public/image/qrcode/'.$param['id'].'.png');
        Model::update( ['img_url' => fullDomain('api').'/image/qrcode/'.$param['id'].'.png'], ['id' => $param['id']]);

        $this->success('二维码生成成功!', fullDomain('api').'/image/qrcode/'.$param['id'].'.png');

    }
    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id'],
            ['v' => 'title', 'label' => '渠道名称'],
            ['v' => 'link', 'label' => '链接'],
            ['v' => 'img_url', 'label' => '二维码', "render"=>"image"],
            ['v' => 'created_at', 'label' => '日期', 'searchType' => 'number', 'sort' => 'created_at'],
            ['v' => 'remark', 'label' => '备注'],
        ];
    }
}
