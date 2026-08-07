<?php
declare(strict_types=1);

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\GameAccount as GameAccountModel;
use think\response\Json;

class GameAccount extends BaseController
{
    /**
     * 列表字段定义
     */
    public function columns(): array
    {
        return [
            ['field' => 'id',        'name' => 'ID',           'width' => 80,  'searchType' => 'number'],
            ['field' => 'account',   'name' => '账号',         'width' => 120, 'searchType' => 'like'],
            ['field' => 'password',  'name' => '密码',         'width' => 120, 'hidden'  => true],
            ['field' => 'status',    'name' => '状态',         'width' => 80,  'searchType' => 'match'],
            ['field' => 'remark',    'name' => '备注',         'minWidth'=>150, 'searchType' => 'like'],
            ['field' => 'created_at','name' => '创建时间',     'width' => 160, 'searchType' => 'daterange'],
            ['field' => 'updated_at','name' => '更新时间',     'width' => 160],
        ];
    }

    /**
     * 列表
     */
    #[Permission]
    public function index(): Json
    {
        $list = $this->tableList(GameAccountModel::class)
            ->selectData();

        // 虚拟字段：状态名称翻译
        $list->each(function ($item) {
            $item['status_name'] = GameAccountModel::$STATUS_MAP[$item['status'] ?? 0] ?? '未知';
        });

        return $this->success([
            'list'  => $list,
            'count' => $this->tableList(GameAccountModel::class)->count(),
        ]);
    }

    /**
     * 下拉选项
     */
    #[Permission]
    public function select(): Json
    {
        $list = GameAccountModel::field('id,account')->select();
        return $this->success(['list' => $list]);
    }

    /**
     * 详情
     */
    #[Permission]
    public function get(): Json
    {
        $id  = $this->request->param('id', 0);
        $row = GameAccountModel::find($id);
        return $row ? $this->success(['info' => $row]) : $this->success([], '暂无数据');
    }

    /**
     * 新增
     */
    #[Permission]
    public function add(): Json
    {
        return $this->mAdd(new GameAccountModel);
    }

    /**
     * 编辑
     */
    #[Permission]
    public function edit(): Json
    {
        return $this->mEdit(new GameAccountModel);
    }

    /**
     * 删除
     */
    #[Permission]
    public function delete(): Json
    {
        return $this->mDelete(new GameAccountModel);
    }

    /**
     * 修改状态
     */
    #[Permission]
    public function status(): Json
    {
        $id     = $this->request->param('id', 0);
        $status = $this->request->param('status', 0);
        GameAccountModel::where('id', $id)->update(['status' => $status]);
        return $this->success([], '操作成功');
    }
}
