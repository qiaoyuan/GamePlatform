<?php

namespace app\common\model;

use app\common\traits\UserWith;

/**
 * @property int $id
 * @property string $phone 手机
 * @property string $created_at 发送时间
 * @property string $id_code 验证码
 * @property string $msg 短信内容
 * @property int $user_id 发送人
 * @property int $status 发送状态
 * @property string $ip 发送IP
 * @property string $return_data 返回详情
 * @property int $check_count 验证次数
 * @property int $sms_type 短信类型
 */
class SmsReport extends Base
{
    use UserWith;
    protected $table = 'sms_report';
    protected $pk = 'id';
    protected $updateTime = false;
    protected $field = [
        'id',
        'phone',
        'created_at',
        'id_code',
        'msg',
        'user_id',
        'status',
        'ip',
        'return_data',
        'check_count',
        'sms_type',
    ];
    protected $type = [];

    public static function checkCode(string $phone, string $code, bool $reset = true): bool
    {
        if (isLocal()) {
            return true;
        }
        if (!$code) {
            return false;
        }
        //5分钟内单次有效
        $sms = SmsReport::where('phone', trim($phone))
            ->where('id_code', '<>', '')
            ->where('created_at', '>', time() - 300)
            ->order('id', 'DESC')
            ->find();
        $sendCode = '';
        if ($sms) {
            $sendCode = $sms->id_code;
            if ($sendCode && $sendCode === trim($code)) {
                $sms->id_code = '';
            }else{
                $sendCode = '';
                $sms->check_count += 1;
                if ($sms->check_count > 2) {
                    $sms->id_code = '';
                }
            }
            $sms->save();
        }
        if ($sendCode) {
            return true;
        }
        return false;
    }
}
