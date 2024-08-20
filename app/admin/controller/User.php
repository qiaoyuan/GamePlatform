<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\User as Model;
use think\helper\Arr;
use app\common\annotation\Permission;


class User extends BaseController
{
    #[Permission(title: '微信会员', isMenu: 1, parentUrl: 'article', isHideSub: 1)]
    public function index()
    {
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['title', 'description'])->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }

    #[Permission(title: '会员编辑')]
    public function edit()
    {
        $data = $this->validate('edit');
        if (isset($data['password']) && $data['password']) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        Model::update(Arr::only($data, [
            'nickname',
            'phone',
            'password',
            'avatar'
        ]), ['id' => $data['id']]);
        $this->success('修改成功');
    }


    #[Permission(title: '会员删除')]
    public function delete()
    {
        $this->mDelete(Model::class);
    }

    public function get()
    {
        $this->success('', [
            'detail' => Model::find(input('id'))
        ]);
    }

    public function create()
    {
        $this->success('', [
            'frozenType' => [],
        ]);
    }

//    public function status()
//    {
//        $status = input('status', 0);
//        Model::update(['status' => $status], ['id' => $this->request->post('id')]);
//        $this->success('修改成功', ['status' => $status]);
//    }
//
    public function select()
    {
        $this->success('', [
            'list' => $this->tableList(Model::class, [], ['nickname', 'phone', 'id'])
                ->field('nickname as label,id as value')
                ->limit(20)
                ->select()
        ]);
    }

    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id'],
            ['v' => 'username', 'label' => '用户名'],
            ['v' => 'nickname', 'label' => '昵称'],
            ['v' => 'avatar', 'label' => '头像', 'render' => 'image', 'search' => false],
            ['v' => 'phone', 'label' => '电话'],
            ['v' => 'open_id', 'label' => '对于微信商家唯一标'],
            ['v' => 'channel_id', 'label' => '渠道id'],
        ];
    }
}
