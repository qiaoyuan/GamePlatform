<?php

namespace app\common\model;

use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

/**
 * @property int $id
 * @property string $title 分类名称
 * @property string $module
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property int $status 状态
 * @property int $parent_id
 * @property int $sort 排序
 * @property string $icon_url icon图片
 * @property string $description 简述
 */
class ArticleCategory extends Base
{
    protected $table = 'article_category';
    protected $pk = 'id';
    protected $field = [
        'id',
        'title',
        'module',
        'created_at',
        'updated_at',
        'deleted_at',
        'status',
        'parent_id',
        'sort',
        'icon_url',
        'description',
    ];
    protected $type = [];

    public function getList($where, $field="*", $order, $page=0, $limit=50)
    {
        return self::where($where)
            ->field($field)
            ->order($order)
            ->page($page, $limit)
            ->select();
    }

    public function questionnaires(): HasMany
    {
        return $this->hasMany(Questionnaires::class, 'article_category_id', 'id');
    }

}
