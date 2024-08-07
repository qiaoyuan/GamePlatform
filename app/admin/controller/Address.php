<?php
namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\Base;
use think\facade\Db;
use app\common\model\Address as Model;

class Address extends BaseController
{
    /**
     * @permission_parent_url setting
     * @permission_title 地址管理
     * @permission_is_menu
     */
    public function index()
    {
        $where = [['pid', '=', $this->request->post('id', 0)]];
        $lists = $this->tableList(Model::class, ['sort' => Base::SORT_FIELD_TYPE, 'id' => 'ASC'])
            ->where($where)
            ->where('level', '<', 4)
            ->append(['has_children'])
            ->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }

    /**
     * @permission_title 添加地址
     */
    public function add()
    {
        $pid = request()->post('pid');
        $data = ['level' => 1];
        if ($pid) {
            $parent = Model::find($pid);
            if (!$parent) {
                $this->error('上级不存在');
            }
            $data['level'] = $parent['level'] + 1;
            if ($data['level'] > 4) {
                $this->error('最多4级');
            }
        }
        $this->mAdd('Address', ['append' => $data]);
    }

    /**
     * @permission_title 编辑地址
     */
    public function edit()
    {
        $id = input('id');
        $info = Model::find($id);
        $data = ['pid' => $info['pid'], 'level' => $info['level']];
        $this->mEdit('Address', ['append' => $data]);
    }

    /**
     * @permission_title 删除地址
     */
    public function delete()
    {
        $id = $this->getInputPk();
        $sub = (new Model())->where('pid', 'IN', $id)->value('id');
        if ($sub && $this->request->admin_id != 1) {
            $this->error('存在子数据，不能删除');
        }
        Model::remove($id);
        $this->success();
    }

    public function select()
    {
        $lists = $this->tableList(Model::class, ['sort' => 'DESC', 'id' => 'ASC'])
            ->field('id as value, name as label, level')
            ->select()->toArray();
        foreach ($lists as &$list) {
            $list['leaf'] = $list['level'] > 2;
        }
        $this->success('', [
            'list' => $lists
        ]);
    }

    public function selectTree()
    {
        $list = Db::table('address')
            ->field('id as value, name as label, pid')
            ->where('level', '<', 4)
            ->select()
            ->toArray();
        $list = toTree($list, 'value', 'pid');
        $this->success('', [
            'list' => $list
        ]);
    }
}
