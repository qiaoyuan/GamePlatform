<?php
namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\Admin as Model;

class Admin extends BaseController
{

    /**
     * @permission_parent_url system
     * @permission_title 管理员
     * @permission_is_menu
     * @permission_sort 9
     */
    public function index()
    {
        $lists = $this->tableList(Model::class, ['id' => 'ASC'])
            ->where([['id', '>', 1]])
            ->hidden(['password'])
            ->append(['status_text'])
            ->selectData();
        $this->success('', [
            'list' => $lists,
        ]);
    }

    /**
     * @permission_title 添加管理员
     */
    public function add()
    {
        $data = request()->post();
        $this->validate('admin.add', $data);
        $password = $data['password'] ?: '';
        $data['password'] = setPassWord($password);
        $data['reg_at'] = dateNow();
        $data['reg_ip'] = request()->ip();
        $data['reg_admin_id'] = $this->request->admin_id;
        $admin = Model::create($data);
        $this->success('', [
            'detail' => $admin->hidden(['password']),
        ]);
    }

    /**
     * @permission_title 编辑管理员
     */
    public function edit($id)
    {
        $data = request()->put();
        $this->validate('admin.edit', $data);
        if (isset($data['password']) && $data['password']) {
            $data['password'] = setPassWord(trim($data['password']));
        } elseif (isset($data['password'])) {
            unset($data['password']);
        }
        $admin = Model::find($id);
        if ($admin) {
            $admin->save($data);
            $this->success('', $admin->hidden(['password']));
        } else {
            $this->error('数据不存在');
        }
    }

    /**
     * @permission_title 删除管理员
     */
    public function delete()
    {
        $this->error('暂不支持');
    }


    /**
     * @permission_title 修改管理员状态
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
            'list' => $this->tableList(Model::class)->field('nickname as label,id as value, status')->where('status', 1)->select()
        ]);
    }

    public function columns(): array
    {
        return [
            ['v' => 'id', 'label' => 'ID', 'searchType' => 'match', 'sort' => 'id'],
            ['v' => 'username', 'label' => '账号'],
            ['v' => 'nickname', 'label' => '名称'],
            ['v' => 'phone', 'label' => '电话'],
            // [
            //     'v' => 'admin_department_id',
            //     'label' => '部门',
            //     'sort' => 'admin_department_id',
            //     'searchType' => 'multiple',
            //     'searchList' => '/adminDepartment/select',
            //     'replace' => true,
            // ],
            [
                'v' => 'reg_admin_id',
                'label' => '创建人',
                'sort' => 'reg_admin_id',
                'searchType' => 'multiple',
                'searchList' => '/admin/select',
                'replace' => true,
            ],
            ['v' => 'reg_at', 'label' => '添加时间', 'searchType' => 'daterange', 'sort' => 'reg_at'],
            ['v' => 'status', 'label' => '状态', 'render' => 'status', 'sort' => 'status'],
            ['v' => 'last_login_ip', 'label' => '最近登录IP'],
            ['v' => 'last_login_at', 'label' => '最近登录时间', 'searchType' => 'daterange', 'sort' => 'last_login_at'],
        ];
    }
}
