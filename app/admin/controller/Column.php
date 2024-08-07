<?php
namespace app\admin\controller;

use app\admin\BaseController;
use app\common\model\AdminColumn;
use think\facade\Cache;
use think\helper\Arr;
use think\helper\Str;

class Column extends BaseController
{
    public function get()
    {
        $data = $this->request->post();
        $tabName = $data['tab_name'];
        [$controller, $action] = explode('/', trim($tabName, '/'));
        $controller = '\\app\\admin\\controller\\' . Str::studly($controller);
        $controller = new $controller($this->app);
        $columns = $controller->$action();
        if (empty($data['is_code'])) {
            $columns = fastCache(json_encode($data), function () use ($data, $columns) {
                $key = md5(implode('_', [$data['tab_name'], $data['tab'] ?? 0]));
                $cache = AdminColumn::where('key', $key)->find();
                $cache = $cache ? ($cache->rule ?: []) : [];
                if ($cache) {
                    $columns = array_column($columns, null, 'v');
                    $result = [];
                    foreach ($cache as $item) {
                        if (isset($columns[$item['v']])) {
                            $keys = ['width', 'minWidth','fixed'];
                            $result[] = array_merge($columns[$item['v']],
                                array_filter($item, function ($val, $key) use ($keys) {
                                    if (in_array($key, $keys)) {
                                        return true;
                                    }
                                    if ($val === '') { //过滤用户未配置的参数
                                        return false;
                                    }
                                    return true;
                                }, ARRAY_FILTER_USE_BOTH));
                            unset($columns[$item['v']]);
                        }
                    }
                    if ($columns) {
                        $result = array_merge($result, array_values($columns));
                    }
                } else {
                    $result = $columns;
                }
                return $result;
            }, null, $tabName);
        }
        if ($this->request->post('_recycle')) {
            array_push($columns, [
                'v' => 'deleted_at', 'label' => '删除时间', 'searchType' => 'daterange', 'width' => 140
            ]);
        }
        $this->success('', [
            'list' => $columns
        ]);
    }

    public function saveCommon()
    {
        $data = $this->request->post();
        $key = md5(implode('_', [$data['tab_name'], $data['tab'] ?? 0]));
        AdminColumn::create([
            'tab_name' => $data['tab_name'],
            'param' => json_encode(Arr::except($data, ['rule'])),
            'rule' => $data['rule'],
            'key' => $key,
        ], [], true);
        $this->success();
    }

    public function refresh()
    {
        $data = $this->request->post();
        Cache::tag($data['tab_name'])->clear();
        $this->success('操作成功');
    }
}
