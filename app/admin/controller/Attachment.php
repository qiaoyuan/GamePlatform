<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\Attachment as AttachModel;

class Attachment extends BaseController
{
    protected array $log_ignore = ['index', 'recent'];

    /**
     * @permission_parent_url article
     * @permission_title 附件管理
     * @permission_is_menu
     */
    public function index()
    {
        $lists = $this->tableList(AttachModel::class, ['id' => 'DESC'])
            ->append(['url'])
            ->selectData();
        $upload = config('upload.admin');
        $this->success('', [
            'list' => $lists,
            'upload' => $upload,
        ]);
    }

    /**
     * @permission_title 删除附件
     */
    public function delete()
    {
        $ids = request()->post('id/a');
        AttachModel::deleteById($ids);
        $this->success();
    }

    public function recent()
    {
        $list = $this->tableList(AttachModel::class, ['id' => 'Desc'])->where([
            ['admin_id', '=', $this->request->admin_id]
        ])->append(['src'])->selectData();
        $this->success('', [
            'list' => $list
        ]);
    }

    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'search' => 'id', 'searchType' => 'match', 'width' => '60'],
            ['v' => 'filename', 'label' => '文件名'],
            ['v' => 'path', 'label' => '路径'],
            ['v' => 'url', 'label' => '附件', 'search' => false, 'render' => 'attachment'],
            ['v' => 'size', 'label' => '大小', 'width' => '80', 'searchType' => 'number'],
            ['v' => 'created_at', 'label' => '时间', 'width' => '140', 'searchType' => 'daterange'],
        ];
    }
}
