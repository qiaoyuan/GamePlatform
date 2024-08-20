<?php

namespace app\common\model;

use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

/**
 * @property int $id 答题唯一标示
 * @property int $question_id 问题ID
 * @property string $answer_text 用户回答文案，选择题默认为空
 * @property string $created_at
 * @property string $updated_at
 * @property int $option_id 选项id
 * @property int $uid 用户id
 * @property int $questionnaire_id 问卷ID
 */
class QuestionAnswers extends Base
{
    protected $autoWriteTimestamp = true;

    protected $table = 'question_answers';
    protected $pk = 'id';
    protected $field = [
        'id',
        'question_id',
        'answer_text',
        'created_at',
        'updated_at',
        'option_id',
        'uid',
        'questionnaire_id',
    ];
    protected $type = [];

    public function questions()
    {
        return $this->belongsTo(Questions::class, 'question_id', 'id');
    }

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaires::class, 'questionnaire_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uid', 'id');
    }

    public function questionOptions(): BelongsTo
    {
        return $this->belongsTo(QuestionsOptions::class, 'option_id', 'id');
    }

    public function getUserAnswerScore($questionnaireId, $uid)
    {
        $optionIds = self::where('questionnaire_id', $questionnaireId)
        ->where('uid', $uid)->value('option_id');
        if (empty($optionIds)) {
            return [];
        }
        return QuestionsOptions::whereIn('id', $optionIds)->sum('score');
    }

}
