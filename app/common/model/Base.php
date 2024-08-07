<?php

namespace app\common\model;

use think\helper\Arr;
use think\helper\Str;
use think\Model;
use think\model\relation\HasMany;
use think\model\relation\OneToOne;

class Base extends Model
{
    const SORT_FIELD_TYPE = 'DESC';

    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    protected $autoWriteTimestamp = 'datetime';

    protected string $error = '';

    public function getDbFields(): array
    {
        return $this->field;
    }

    /**
     * 关联添加
     * @param array $data
     * @param array $assocCollection 关联模型集合
     * @param bool $replace
     * @return self
     */
    public static function assocAdd(array $data, array $assocCollection, bool $replace = false): self
    {
        $mainModel = self::create($data, [], $replace);
        foreach ($assocCollection as $key => $assocName) {
            $callback = null;
            //有关联键时回调
            if (!is_numeric($key)) {
                $callback = $assocName;
                $assocName = $key;
            }
            if (method_exists($mainModel, $assocName)
                && ($mainModel->$assocName() instanceof OneToOne || $mainModel->$assocName() instanceof HasMany)) {
                $assocData = $data[Str::snake($assocName)] ?? $data;
                if ($assocData) {
                    if (method_exists($mainModel->$assocName(), 'saveAll')) {
                        $mainModel->$assocName()->saveAll($assocData);
                    } else {
                        $mainModel->$assocName()->save($assocData);
                    }
                }
                if (is_callable($callback)) {
                    $callback($mainModel->$assocName);
                }
            }
        }
        return $mainModel;
    }

    public function assocStore($data, $with, $foreignKey = '', $localKey = ''): self
    {
        $this->save(Arr::except($data, $with));
        foreach ($with as $key => $relation) {
            $callback = null;
            if (!is_numeric($key)) {
                $callback = $relation;
                $relation = $key;
            }
            if (method_exists($this, $relation)) {
                $assocData = $data[Str::snake($relation)] ?? $data;
                if ($assocData) {
                    if (method_exists($this->$relation(), 'saveAll')) {
                        $this->$relation()->where($foreignKey, $this->$localKey)->delete();
                        $this->$relation()->saveAll($assocData);
                    } elseif ($this->$relation()) {
                        $this->$relation()->save($assocData);
                    }
                }
                if (is_callable($callback)) {
                    $callback($this->$relation);
                }
            }
        }
        return $this;
    }

    public function getError() :string
    {
        return $this->error;
    }

    protected function formatDateTime($format, $time = 'now', bool $timestamp = false)
    {
        if ($time === 0) {
            return '';
        }
        return parent::formatDateTime($format, $time, $timestamp);
    }

    public function binToArray($value, $map): array
    {
        $r = [];
        foreach ($map as $k => $v)
        {
            $k & $value && $r[] = $k;
        }
        return $r;
    }

    public function arrayToBin($arr): int
    {
        return array_reduce($arr, function ($carry, $item) {
            $carry |= $item;
            return $carry;
        });
    }

    public function getStatusTextAttr($value, array $data): string
    {
        return $data['status'] === 1 ? '正常' : '禁用';
    }

    public static function getSelectList()
    {
        return fastCache(static::class, function () {
            return static::column('title', 'id');
        }, null, 'select');
    }
}
