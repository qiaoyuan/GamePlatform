<?php
namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\AdminConfig;
use think\helper\Arr;
use util\Huawei;

class Config extends BaseController
{
    /**
     * @permission_parent_url system
     * @permission_title 配置
     * @permission_is_menu
     * @permission_sort 10
     */
    public function index()
    {

    }

    /**
     * @permission_title 修改配置
     */
    public function save()
    {
        $data = $this->request->post();
        $data['type'] = $data['type'] ?? AdminConfig::TYPE_ARRAY;
        $this->validate('admin_config.save', $data);
        $config = AdminConfig::where('name', $data['name'])->find();
        if (!$config) {
            $data['status'] = 1;
            $data['sort'] = 0;
            AdminConfig::create($data);
        } else {
            $config->value = $data['value'];
            $config->save();
        }
        if (in_array($data['name'], ['dll_json', 'ip_white_list', 'track_string_jd', 'track_string_tb'])) {
            clearApiCache('clear_config_key', ['key' => $data['name']]);
        }
        if ($data['name'] == 'dll_json') {
            Huawei::refresh(['http://dd.shopshop123.cn/v1/j']);
        }
        clearCache('admin_config');
        $this->success('修改成功');
    }

    public function get()
    {
        $config = AdminConfig::where('name', $this->request->post('name'))->find();
        $this->success('', [
            'detail' => $config
        ]);
    }

    /**
     * @permission_parent_url ext
     * @permission_title 转链配置
     * @permission_is_menu
     * @permission_sort 3
     */
    public function trans()
    {
        $hitTypes = \app\common\model\HitType::where('is_trans', 1)->select();
        $r = AdminConfig::getConfigValue(AdminConfig::getExtConfigKey(AdminConfig::TRANS_KEY, input('m')));
        if (!isset($r[0][0])) {
            $r = [];
        }
        $result = [[], []];
        foreach ($hitTypes as $hitType) {
            $row = [
                'cid' => $hitType->id,
                'title' => $hitType->title,
                'pid' => '',
                'authId' => '',
                'unionId' => '',
                'siteId' => '',
                'positionId' => '',
            ];
            foreach ($result as $rK => &$rRow) {
                if (isset($r[$rK])) {
                    foreach ($r[$rK] as $rItem) {
                        if ($rItem['cid'] == $hitType->id) {
                            $row = array_merge(
                                $row,
                                Arr::only($rItem, ['pid', 'authId', 'unionId', 'siteId', 'positionId'])
                            );
                            break;
                        }
                    }
                }
                $rRow[] = $row;
            }
        }
        $this->success('', $result);
    }

    public function saveTrans()
    {
        $key = AdminConfig::getExtConfigKey(AdminConfig::TRANS_KEY, input('m'));
        $data = $this->request->post();
        $config = AdminConfig::where('name', $key)->find();
        if (!$config) {
            $data['status'] = 1;
            $data['sort'] = 0;
            $data['name'] = $key;
            $data['title'] = '转链参数';
            $data['group'] = 2;
            $data['type'] = AdminConfig::TYPE_ARRAY;
            AdminConfig::create($data);
        } else {
            $config->value = $data['value'];
            $config->save();
        }
        cache('admin_config', null);
        $this->success('保存成功');
    }
}
