<?php

namespace app\common\model;

/**
 * @property int $id
 * @property int $status
 * @property string $created_at
 * @property string $updated_at
 * @property string $title
 * @property string $img_url
 * @property string $link
 */
class UserChannel extends Base
{
    protected $autoWriteTimestamp = true;

    protected $table = 'user_channel';
    protected $pk = 'id';
    protected $field = [
        'id',
        'status',
        'created_at',
        'updated_at',
        'title',
        'img_url',
        'link',
    ];
    protected $type = [];
}
