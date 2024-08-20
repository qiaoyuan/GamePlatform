<?php

namespace app\common\model;

use think\model\relation\BelongsTo;

/**
 * @property int $id
 * @property string $title 测试名称
 * @property string $description 测试描述
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property string $img_url 图片
 * @property int $article_category_id 类型
 * @property string $easy 题目易懂
 * @property string $exact 结果准确性
 * @property string $utility 建议实用性
 * @property int $sort 排序/问题序号
 */
class Questionnaires extends Base
{
    protected $autoWriteTimestamp = true;


    protected $table = 'questionnaires';
    protected $pk = 'id';
    protected $field = [
        'id',
        'title',
        'description',
        'created_at',
        'updated_at',
        'status',
        'img_url',
        'article_category_id',
        'easy',
        'exact',
        'utility',
        'sort',
    ];
    protected $type = [];

    public function articleCategorys() :BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id', 'id');
    }
}
