<?php

namespace app\common\model;

use app\common\model\trait\UserRelation;
use app\common\model\trait\VerifyStatus;
use think\model\relation\BelongsTo;
use think\model\relation\HasOne;

/**
 * @property int $id
 * @property string $title 标题
 * @property string $module
 * @property int $article_category_id 分类
 * @property string $thumb 封面图(upload)
 * @property int $status 状态
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property string $desc 简介
 * @property int $admin_id 添加人
 * @property int $sort 排序
 * @property int $is_index 是否首页推荐
 */
class Article extends Base
{
    protected $table = 'article';
    protected $pk = 'id';
    protected $field = [
        'id',
        'title',
        'module',
        'article_category_id',
        'thumb',
        'status',
        'created_at',
        'updated_at',
        'deleted_at',
        'desc',
        'admin_id',
        'sort',
        'is_index',
    ];
    protected $type = [];

    public function articleContent(): HasOne
    {
        return $this->hasOne(ArticleContent::class, 'article_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id', 'id');
    }
}
