<?php

namespace app\common\model;

/**
 * @property int $user_third_id
 * @property string $content
 */
class UserThirdOriginal extends Base
{
    protected $table = 'user_third_original';
    protected $pk = 'user_third_id';
    protected $autoWriteTimestamp = false;
    protected $field = [
        'user_third_id',
        'content',
    ];
    protected $type = [];
}
