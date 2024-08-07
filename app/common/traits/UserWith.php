<?php
namespace app\common\traits;

use app\common\model\User;
use think\model\relation\BelongsTo;

trait UserWith {
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->hidden(['password']);
    }
}
