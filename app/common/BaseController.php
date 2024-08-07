<?php
declare (strict_types = 1);

namespace app\common;

use think\db\Query;
use app\common\traits\Jump;
use app\Request;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\helper\Str;
use think\Validate;

/**
 * 控制器基础类
 */
class BaseController
{

    use Jump;
    /**
     * Request实例
     * @var Request
     */
    protected Request $request;

    /**
     * 应用实例
     * @var App
     */
    protected App $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected bool $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected array $middleware = [];

    private array $conditionTypeMap = [
        'like' => 'LIKE',
        'min' => '>=',
        'max' => '<=',
        'start' => '>=',
        'end' => '<=',
        'match' => 'IN',
        'multiple' => 'IN',
        'range' => 'BETWEEN',
        'between' => 'BETWEEN',
        'empty' => '='
    ];

    public array $breads = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    protected function addBread($name, $url = '')
    {
        $this->breads[$name] = $url;
    }

    /**
     * 验证数据
     * @access protected
     * @param array|string $validate 验证器名或者验证规则数组
     * @param  array        $data     数据
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array
     * @throws ValidateException
     */
    protected function validate($validate = '', array $data = [], array $message = [], bool $batch = false) :array
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            } else {
                $scene = $validate;
                $validate = $this->getValidateName();
            }
            $class = str_contains($validate, '\\') ? $validate : ('app\\common\\validate\\' . Str::studly($validate));
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }
        $data = $data ?: request()->param();
        fullTrim($data);
        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        if(!$v->check($data)) {
            $this->error($v->getError());
        }
        return $data;
    }

    /**
     * 获取对应模型名称
     *
     * @return string model
     */
    protected function getModelName(): string
    {
        $class = get_class($this);
        return substr($class, strrpos($class, '\\') + 1);
    }

    /**
     * 获取对应模型名称
     *
     * @return string model
     */
    protected function getValidateName(): string
    {
        $class = get_class($this);
        return substr($class, strrpos($class, '\\') + 1);
    }

    /**
     *
     * 只解析查询参数和排序，需要自己调用paginate或者select来查询参数
     *
     * @param string $model 模型  当请求参数中带有模型中以及withJoin的模型中相关字段时，会自动处理搜索功能
     * @param mixed $order 默认排序，会被请求来的排序覆盖
     * @param array $joinModel join查询的模型，包括join相关的和withJoin
     * @return Query
     */
    protected function tableList(string $model, $order = [], $kFields = [], array $joinModel = []): Query
    {
        $class = str_contains($model, '\\') ? $model : ('app\\common\\model\\' . Str::studly($model));
        $model = new $class();
        $fields = $model->getDbFields();
        $allFields = $fields;
        foreach ($joinModel as $item) {
            $allFields = array_merge((new $item)->getDbFields(), $allFields);
        }
        $where = $this->getCondition($allFields);
        if (in_array('deleted_at', $fields)) {
            if ($this->request->post('_recycle')) {
                $where[] = [$model->getTable() . '.deleted_at', 'NOTNULL', null];
            } else {
                $where[] = [$model->getTable() . '.deleted_at', 'NULL', null];
            }
        }
        if (input('k') && $kFields) {
            $where[] = function (Query $query) use ($kFields) {
                foreach ($kFields as $field) {
                    if (is_string($field)) {
                        $query->whereOr($field, 'like', '%' . input('k') . '%');
                    }
                    if (is_array($field)) {
                        $query->whereOr($field[0], $field[1], function (Query $query) use ($field) {
                            $field[2]($query, '%' . input('k') . '%');
                        });
                    }
                }
            };
        }
        $this->getListOrder($order, $allFields);
        if (is_string($order)) {
            $order = Db::raw($order);
        }
        return $model->where($where)->order($order);
    }

    protected function getListOrder(&$order, array $fields = [])
    {
        $sort = input('sort');
        if ($sort) {
            if (Str::startsWith($sort, ['+', '-'])) {
                $sort_type = substr($sort, 0, 1);
                $sort_type = $sort_type == '+' ? 'ASC' : 'DESC';
                $sort = substr($sort, 1);
            } else {
                $sort_type = 'DESC';
            }
            if (str_contains($sort, '__')) {
                $sort = explode('__', $sort);
                if (in_array($sort[1], $fields)) {
                    $sort = implode('.', $sort);
                }
            }
            $sorts = explode(',', $sort);
            $order = [];
            foreach ($sorts as $sort) {
                if (in_array($sort, $fields)) {
                    $order[$sort] = $sort_type;
                }
            }
        }
    }

    /**
     * 范围或指定条件查询
     *
     * @param array $fields
     */
    protected function getCondition(array $fields): array
    {
        $params = $this->request->param();
        $where = [];
        foreach ($params as $key => $value) {
            if (!str_contains($key, '_') || $value === '' || $value === null) {
                continue;
            }
            if (str_contains($key, '__')) {
                [$table, $key] = explode('__', $key);
            } else {
                $table = null;
            }
            $type = substr($key, strrpos($key, '_') + 1);
            $field = substr($key, 0, strrpos($key, '_'));
            if (!$type || !isset($this->conditionTypeMap[$type]) || !in_array($field, $fields)) {
                continue;
            }
            if ($table) {
                $field = implode('.', [$table, $field]);
            }
            $condition = [$field, $this->conditionTypeMap[$type]];
            switch ($type) {
                case 'start':
                    $condition[] = strtotime($value);
                    break;
                case 'end':
                    if (strlen($value) == 10) {
                        $condition[] = strtotime($value) + 86399;
                    } else {
                        $condition[] = strtotime($value);
                    }
                    break;
                case 'like':
                    $condition[] = '%' . $value . '%';
                    break;
                case 'match':
                case 'multiple':
                    $condition[] = is_array($value) ? $value : explode(',', (string)$value);
                    break;
                case 'range':
                    if (is_string($value)) {
                        $range = explode(',', $value);
                    } else {
                        $range = $value;
                    }
                    $day = '';
                    if (strlen($range[1]) == 10) {
                        $day = ' 23:59:59';
                    }
                    $condition[] = [$range[0], $range[1] . $day];
                    break;
                case 'between':
                    if (is_string($value)) {
                        $range = explode(',', $value);
                    } else {
                        $range = $value;
                    }
                    $condition[] = [$range[0], $range[1]];
                    break;
                case 'empty':
                    if ($value == 1) {
                        $condition[] = '';
                    }
                    if ($value == -1) {
                        $condition[1] = '<>';
                        $condition[] = '';
                    }
            }
            if (count($condition) == 2) {
                $condition[] = $value;
            }
            $where[] = $condition;
        }
        return $where;
    }

    protected function controller(): string
    {
        return Str::snake($this->request->controller());
    }
}
