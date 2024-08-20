<?php

namespace app\common\model;

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
    ];
    protected $type = [];
}
