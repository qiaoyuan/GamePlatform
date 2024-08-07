<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\common\enum\SmsType;
use app\common\model\SmsReport as Model;
use app\common\traits\UserColumn;
use app\common\annotation\Permission;

class SmsReport extends BaseController
{
    use UserColumn;
    #[Permission(parentUrl: 'system', title: '短信记录', isMenu: 1, sort: 1, isHideSub: 1)]
    public function index()
    {
        $lists = $this->tableList(Model::class, ['id' => 'DESC'], ['phone', 'id_code', 'msg', 'ip', 'return'])
            ->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }

    /**
     * @permission_title 删除短信记录
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

    #[Permission(title: '发送短信')]
    public function send()
    {
        $data = $this->validate('send');
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
            ['v' => 'phone', 'label' => '手机'],
            ['v' => 'created_at', 'label' => '发送时间', 'searchType' => 'daterange', 'sort' => 'created_at'],
            ['v' => 'id_code', 'label' => '验证码'],
            ['v' => 'msg', 'label' => '短信内容'],
            ...$this->getUserColumns(),
            [
                'v' => 'status',
                'label' => '发送状态',
                'sort' => 'status',
                'searchType' => 'multiple',
                'searchList' => mapToSelect([0 => '失败', 1 => '成功']),
                'replace' => true,
            ],
            ['v' => 'ip', 'label' => '发送IP'],
            ['v' => 'return_data', 'label' => '返回详情'],
            ['v' => 'check_count', 'label' => '验证次数', 'searchType' => 'number', 'sort' => 'check_count'],
            [
                'v' => 'sms_type',
                'label' => '短信类型',
                'searchType' => 'multiple',
                'sort' => 'type',
                'searchList' => mapToSelect(SmsType::getMap()),
                'replace' => true,
            ],
        ];
    }
}
