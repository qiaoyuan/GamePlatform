<?php

namespace app\common\model;

use think\model\relation\BelongsTo;

/**
 * @property int $id 答题唯一标示
 * @property float $start 开始
 * @property float $lt 小于等于
 * @property string $updated_at
 * @property int $questionnaire_id 问卷ID
 * @property string $text 返回内容
 */
class QuestionResponse extends Base
{

    protected $autoWriteTimestamp = true;

    protected $table = 'question_response';
    protected $pk = 'id';
    protected $createTime = false;
    protected $field = [
        'id',
        'start',
        'lt',
        'updated_at',
        'questionnaire_id',
        'text',
    ];
    protected $type = [
        'lt' => 'float',
        'gt' => 'float',
    ];

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaires::class, 'questionnaire_id', 'id');
    }


}
