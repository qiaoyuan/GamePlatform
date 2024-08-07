<?php
declare (strict_types = 1);

namespace app\admin;

use app\common\model\AdminLog;
use app\common\model\Base;
use Closure;
use think\helper\Arr;
use think\Model;

class BaseController extends \app\common\BaseController
{
    protected array $log_ignore = ['index', 'select', 'create', 'get', 'count', 'search', 'show', 'read', 'detail', 'tree', 'menus', 'selectTree'];

    protected function initialize()
    {
        AdminLog::log($this->log_ignore);
        parent::initialize();
    }

    public function forceDelete()
    {
        $model = '\\app\\common\\model\\' . $this->getModelName();
        $this->mForceDelete($model);
    }

    public function restore()
    {
        $model = '\\app\\common\\model\\' . $this->getModelName();
        $this->mRestore($model);
    }

    protected function systemError(string $msg = '')
    {
        $this->error($msg ?: '系统忙，请稍后再试，或联系管理员');
    }

    /**
     * @param string $model
     * @param array $param 额外参数
     * @param Closure|null $callback
     * @param array $with 仅支持一对一关系
     */
    protected function mAdd(string $model, array $param = [], array $with = [], Closure $callback = null)
    {
        [$model, $validate] = $this->getModelAndValidate($model);
        $data = $this->request->post();
        if (isset($param['append']) && $param['append']) {
            $data = array_merge($data, $param['append']);
        }
        if (isset($param['except']) && $param['except']) {
            $data = Arr::except($data, $param['except']);
        }
        $validate = new $validate;
        if (!$validate->scene('add')->check($data)) {
            $this->error($validate->getError());
        }
        $model = new $model;
        $r = transaction(function () use ($model, $data, $with, $callback) {
            $r = $model->assocAdd($data, $with);
            if ($r instanceof Model) {
                $model = $r;
                if (is_callable($callback)) {
                    $r = $callback($model);
                    if ($r instanceof Model) {
                        $model = $r;
                    }
                }
                $this->success('添加成功', $model->toArray());
            }
            $this->systemError();
        }, $this);
        $this->systemError($r);
    }

    /**
     * @param string $model
     * @param array $param
     * @param array $with 仅支持一对一关系
     * @param Closure|null $callback
     */
    protected function mEdit(string $model, array $param = [], array $with = [], Closure $callback = null)
    {
        $data = $this->request->put();
        if (isset($param['append']) && $param['append']) {
            $data = array_merge($data, $param['append']);
        }
        if (isset($param['except']) && $param['except']) {
            $data = Arr::except($data, $param['except']);
        }
        [$model, $validate] = $this->getModelAndValidate($model);
        $validate = new $validate;
        $model = new $model;
        $pk = $model->getPk();
        if (!isset($data[$pk])) {
            $data[$pk] = input($pk);
            if (!$data[$pk]) {
                $this->error('修改的数据不存在');
            }
        }
        if (!$validate->scene('edit')->check($data)) {
            $this->error($validate->getError());
        }
        $model = $model->where($pk, $data[$pk])->findOrFail();
        $model->assocStore($data, $with);
        if ($model instanceof Model) {
            if (is_callable($callback)) {
                $r = $callback($model);
                if ($r instanceof Model) {
                    $model = $r;
                }
            }
            $this->success('操作成功', $model->toArray());
        }
        $this->systemError();
    }

    protected function mDelete(string $modelStr, array $with = [], Closure $callback = null)
    {
        $model = new $modelStr;
        $ids = $this->getInputPk($model->getPk());
        if (in_array('deleted_at', $model->getDbFields())) {
            $model->whereIn($model->getPk(), $ids)->update(['deleted_at' => date('Y-m-d H:i:s')]);
            $this->success('删除成功');
        } else {
            $this->mForceDelete($modelStr, $with, $callback);
        }
    }

    protected function mForceDelete(string $modelStr, array $with = [], Closure $callback = null)
    {
        $r = transaction(function () use ($modelStr, $with, $callback) {
            $model = new $modelStr;
            $ids = $this->getInputPk($model->getPk());
            $list = $model->whereIn($model->getPk(), $ids)->with($with)->select();
            foreach ($with as $item) {
                $list->each(function ($m) use ($item) {
                    $m->$item->delete();
                });
            }
            if (is_callable($callback)) {
                $callback($list);
            }
            $modelStr::whereIn($model->getPk(), $ids)->delete();
            $this->success('删除成功');
        });
        $this->systemError($r);
    }

    protected function mRestore(string $modelStr, Closure $callback = null)
    {
        $model = new $modelStr;
        $ids = $this->getInputPk($model->getPk());
        $model->whereIn($model->getPk(), $ids)->update(['deleted_at' => null]);
        if (is_callable($callback)) {
            $callback($ids);
        }
        $this->success('恢复成功');
    }

    /**
     * 移动模型数据的排序
     *
     * @param Model $model 模型
     * @param int $id 需要被移动的数据ID（主键）
     * @param bool $up 是否是向上移动，即排在更前面
     * @param int $step  移动多少位
     * @param array $where 被排序数据的查询条件
     * @param string $field 排序字段
     */
    protected function mSort(Model $model, int $id, bool $up, int $step = 1, array $where = [], string $field = 'sort')
    {
        if (Base::SORT_FIELD_TYPE != 'ASC') {
            $up = !$up;
        }
        $pk = $model->getPk();
        $originalSort = $model->where($pk, $id)->value($field);
        $list = $model->where($where)->where($field, $up ? '<' : '>', $originalSort)
            ->order('sort', $up ? 'DESC' : 'ASC')
            ->field([$pk, $field])
            ->limit($step)
            ->select();
        if (!$list->isEmpty()) {
            $toSort = $list->first()[$field];
            if ($up) {
                $model->where($pk, 'IN', $list->column($pk))->inc($field)->update();
            } else {
                $model->where($pk, 'IN', $list->column($pk))->dec($field)->update();
            }
            $model->where($pk, $id)->update([$field => $toSort]);
        }
        $this->success();
    }

    protected function getCurrentModel(): string
    {
        return $this->getModelAndValidate(get_class($this))[0];
    }

    protected function getModelAndValidate(string $base): array
    {
        if (str_contains($base, '\\')) {
            $base = explode('\\', $base);
            $base = $base[count($base) - 1];
        }
        $validate = 'app\\common\\validate\\' . $base;
        $model = 'app\\common\\model\\' . $base;
        return [$model, $validate];
    }

    protected function getResponseType(): string
    {
        return 'json';
    }

    protected function getInputPk($pk = 'id') :array
    {
        $id = input($pk);
        if ($id) {
            is_array($id) || $id = [$id];
        } else {
            $id = [];
        }
        return $id;
    }
}
