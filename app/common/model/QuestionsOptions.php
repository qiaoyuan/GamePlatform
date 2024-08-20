<?php

namespace app\common\model;

use think\model\relation\BelongsTo;

/**
 * @property int $id
 * @property int $question_id 问题id
 * @property string $title 选项名称
 * @property string $created_at
 * @property string $updated_at
 * @property float $score 分数
 * @property int $sort 顺序
 */
class QuestionsOptions extends Base
{

    protected $autoWriteTimestamp = true;

    protected $table = 'questions_options';
    protected $pk = 'id';
    protected $field = [
        'id',
        'question_id',
        'title',
        'created_at',
        'updated_at',
        'score',
        'sort',
    ];
    protected $type = [
        'score' => 'float',
    ];



    public function questions(): BelongsTo {
        return $this->belongsTo(Questions::class, 'question_id', 'id');

    }

}
