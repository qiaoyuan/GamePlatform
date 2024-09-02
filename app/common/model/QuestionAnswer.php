<?php

namespace app\common\model;

use think\model\relation\BelongsTo;

/**
 * @property int $id 答题唯一标示
 * @property string $json 用户回答文案，选择题默认为空
 * @property string $created_at
 * @property string $updated_at
 * @property int $uid 用户id
 * @property int $questionnaire_id 问卷ID
 * @property float $score 得分
 * @property int $response_id 报告id
 * @property int $group_index 每阶段
 */
class QuestionAnswer extends Base
{
    protected $autoWriteTimestamp = true;

    protected $table = 'question_answer';
    protected $pk = 'id';
    protected $field = [
        'id',
        'json',
        'created_at',
        'updated_at',
        'uid',
        'questionnaire_id',
        'score',
        'response_id',
        'group_index',
    ];
    protected $type = [];

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaires::class, 'questionnaire_id', 'id');
    }
}
