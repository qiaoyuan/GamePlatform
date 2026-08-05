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
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['title', 'description'])->with('channel')->selectData();

        if (!is_numeric($lists)) {
            $lists->each(function (Model $item) {
                $item->channel_name = $item->channel->title ?? '无';
                $item->platform_name = Model::$PLATFORM_MAP[$item->platform] ?? '';
            });

        }
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
            ['v' => 'id', 'label' => 'uid', 'searchType' => 'match', 'sort' => 'id'],
            [
                'v' => 'channel_name',
                'label' => '渠道',
                'search' => 'channel_id',
                'sort' => 'channel_id',
                'searchType' => 'multiple',
                'searchList' => '/userChannel/select',
            ],
            ['v' => 'platform_name', 'search'=>'platform', 'searchType' => 'multiple', 'label' => '平台', 'searchList' => Model::getPlatformList(), 'sort' => 'platform'],
            ['v' => 'username', 'label' => '用户名'],
            ['v' => 'nickname', 'label' => '昵称'],
            ['v' => 'avatar', 'label' => '头像', 'render' => 'image', 'search' => false],
            ['v' => 'phone', 'label' => '电话'],
            ['v' => 'open_id', 'label' => '商家唯一标'],
            ['v' => 'created_at', 'label' => '创建时间', 'searchType' => 'daterange', 'sort' => 'created_at'],

        ];
    }
}
