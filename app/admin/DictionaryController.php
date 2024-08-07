<?php
declare (strict_types = 1);

namespace app\admin;

use think\helper\Str;

/**
 * 控制器基础类
 */
class DictionaryController extends BaseController
{

    public function index()
    {
        $list = $this->tableList($this->getModelName(), ['status' => 'DESC', 'id' => 'DESC'])
            ->append(['status_text'])
            ->selectData();
        $this->success('', [
            'list' => $list
        ]);
    }

    public function add()
    {
        $this->mAdd($this->getModelName(), ['append' => []], [], function ($model) {
            $class = get_class($model);
            if (method_exists($class, 'clearCache')) {
                $class::clearCache();
            }
        });
    }

    public function edit()
    {
        $this->mEdit($this->getModelName(), [], [], function ($model) {
            $class = get_class($model);
            if (method_exists($class, 'clearCache')) {
                $class::clearCache();
            }
        });
    }

    public function status()
    {
        $model = $this->getCurrentModel();
        $model::update(['status' => input('status')], ['id' => input('id')]);
        if (method_exists($model, 'clearCache')) {
            $model::clearCache();
        }
        $this->success('修改成功');
    }

    public function get()
    {
        $model = $this->getCurrentModel();
        $this->success('', [
            'detail' => $model::findOrFail(input('id'))
        ]);
    }

    public function delete()
    {
        $model = $this->getCurrentModel();
        $id = $this->getInputPk();
        $model::where('id', 'IN', $id)->delete();
        if (method_exists($model, 'clearCache')) {
            $model::clearCache();
        }
        $this->success('删除成功');
    }

    public function select()
    {
        $model = $this->getCurrentModel();
        $this->success('', [
            'list' => $model::getSelect()
        ]);
    }

    public function sort()
    {
        $this->mSort(null, input('id'), input('up'), input('step'));
    }

    public function getResponseType(): string
    {
        return 'json';
    }
}
