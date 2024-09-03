<?php

namespace app\common\model;

use think\model\relation\BelongsTo;
use think\model\relation\HasMany;

/**
 * @property int $id
 * @property int $questionnaire_id 问卷id
 * @property string $title 问题名称
 * @property int $question_type 问题类型
 * @property int $status 状态
 * @property int $sort 排序或者题号
 * @property string $created_at
 * @property string $updated_at
* // */
class Questions extends Base
{

    protected $table = 'questions';
    protected $pk = 'id';
    protected $field = [
        'id',
        'questionnaire_id',
        'title',
        'question_type',
        'status',
        'sort',
        'created_at',
        'updated_at',
    ];
    protected $type = [];

    const RADIO_TYPE = 1;
    const MULTI_TYPE = 2;
    const NONE_TYPE = 3;
    public static $QUESTION_TYPE = [
        self::RADIO_TYPE => '单选题',
        self::MULTI_TYPE => '多选题',
        self::NONE_TYPE  => '问答题',
    ];

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaires::class, 'questionnaire_id', 'id');
    }

    public static function getQuestionType()
    {
        return [
            ['label' => self::$QUESTION_TYPE[self::RADIO_TYPE],
                'value' => self::RADIO_TYPE],
            ['label' => self::$QUESTION_TYPE[self::MULTI_TYPE],
                'value' => self::MULTI_TYPE],
            ['label' => self::$QUESTION_TYPE[self::NONE_TYPE],
                'value' => self::NONE_TYPE],
        ];
    }

    public function questionOptions(): HasMany
    {
        return $this->hasMany(QuestionsOptions::class, 'question_id', 'id');
    }

}
