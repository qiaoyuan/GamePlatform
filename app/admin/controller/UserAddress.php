<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\UserAddress as Model;
use think\model\Collection;

class UserAddress extends BaseController
{
    /**
     * @permission_parent_url user
     * @permission_title 用户收货地址
     * @permission_is_menu
     * @permission_sort 1
     * @permission_is_hide_sub
     */
    public function index()
    {
        $where = [];
        if (input('location')) {
            $location = \app\common\model\Address::whereIn('id', input('location'))->select();
            foreach ($location as $item) {
                if ($item->level == 1) {
                    $where[] = ['province_id', '=', $item->id];
                }
                if ($item->level == 2) {
                    $where[] = ['city_id', '=', $item->id];
                }
                if ($item->level == 3) {
                    $where[] = ['region_id', '=', $item->id];
                }
            }
        }
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['detail', 'consignee', 'phone', 'id_card_no'])
            ->where($where)
            ->with(['user'])
            ->selectData();
        if($lists instanceof Collection && !$lists->isEmpty()) {
            $address = \app\common\model\Address::whereIn(
                'id',
                array_merge($lists->column('province_id'), $lists->column('city_id'), $lists->column('region_id'))
            )
                ->column('name', 'id');
            $lists->each(function (Model $model) use ($address) {
                $model->location = implode('', [
                    $address[$model->province_id] ?? '',
                    $address[$model->city_id] ?? '',
                    $address[$model->region_id] ?? ''
                ]);
            });
        }
        $this->success('', [
            'list' => $lists,
        ]);
    }

    /**
     * @permission_title 修改用户收货地址
     */
    public function edit()
    {
        $location = $this->request->post('location');
        if (count($location) < 3) {
            $this->error('请选择地区');
        }
        [$provinceId, $cityId, $regionId] = $location;
        $this->mEdit(Model::class, ['append' => [
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'region_id' => $regionId
        ]], [], function (Model $model) {
            if ($model->is_default) {
                Model::update(
                    ['is_default' => 0],
                    [
                        ['user_id', '=', $model->user_id],
                        ['id', '!=', $model->id],
                        ['is_default', '=', 1]
                    ]
                );
            }
        });
    }

    /**
     * @permission_title 添加用户收货地址
     * @permission_is_menu
     * @permission_is_hide
     */
    public function add()
    {
        $location = $this->request->post('location');
        if (count($location) < 3) {
            $this->error('请选择地区');
        }
        [$provinceId, $cityId, $regionId] = $location;
        $this->mAdd(Model::class, ['append' => [
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'region_id' => $regionId
        ]], [], function (Model $model) {
            if ($model->is_default) {
               Model::update(
                   ['is_default' => 0],
                   [
                       ['user_id', '=', $model->user_id],
                       ['id', '!=', $model->id],
                       ['is_default', '=', 1]
                   ]
               );
            }
        });
    }

    /**
     * @permission_title 删除用户收货地址
     */
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

    /**
     * @permission_title 修改用户收货地址状态
     */
    public function status()
    {
        $status = input('status', 0);
        Model::update(['status' => $status], ['id' => $this->request->post('id')]);
        $this->success('修改成功', ['status' => $status]);
    }

    public function select()
    {
        $this->success('', [
            'list' => $this->tableList(Model::class)->field('title as label,id as value')->select()
        ]);
    }

    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id'],
            [
                'v' => 'user.nickname',
                'label' => '邀请人',
                'sort' => 'user_id',
                'searchType' => 'remote',
                'searchOption' => [
                    'remoteUrl' => '/user/select',
                    'key' => 'k'
                ],
            ],
            [
                'v' => 'location',
                'label' => '地区',
                'sort' => 'province_id,city_id,region_id',
                'search' => 'location',
                'searchType' => 'radio',
                'searchList' => '/address/selectTree',
            ],
            ['v' => 'detail', 'label' => '详细地址'],
            ['v' => 'created_at', 'label' => '日期', 'searchType' => 'daterange', 'sort' => 'created_at'],
            ['v' => 'is_default', 'label' => '默认地址', 'render' => 'boolean', 'sort' => 'is_default'],
            ['v' => 'consignee', 'label' => '收件人'],
            ['v' => 'phone', 'label' => '电话'],
            ['v' => 'id_card_no', 'label' => '身份证'],
        ];
    }
}
