<?php

namespace util;

use AlibabaCloud\Client\AlibabaCloud;
use app\common\model\AdminConfig;
use app\common\model\SmsReport;
use think\helper\Arr;

class Sms
{
    private string $error;

    private int $error_code = 0;

    private int $day_max_count = 20; //单天最大发送次数

    protected array $phone; //当前电话/电话组
    protected string $code;

    protected array $config = [
        'key' => '',
        'secret' => '',
        'switch' => 1,
        'region_id' => '',
        'sign_name' => '',
    ];

    public static array $whiteList = ['18980988947'];

    public function __construct()
    {
        $config = AdminConfig::getConfigValue('sms_config', []);
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 发送短信验证码并记录
     * @param array $phone
     * @param string $template
     * @param array $data
     * @return bool 发送状态 1成功
     */
    public function send(array $phone, string $template, array $data = []): bool
    {
        if ($this->config['switch'] != 1) {
            return $this->error('系统繁忙，请稍后再试');
        }
        $this->phone = $phone;
        if (!$this->checkSend($phone, $data['ip'] ?? '', $data['is_admin'] ?? false)) {
            return false;
        }
        if (isLocal($data['env'] ?? null)) {
            $return = true;
        } else {
            //非开发阶段发送短信
            $return = $this->_post($phone, $template, Arr::except($data, ['ip', 'is_admin', 'env', 'sms_type']));
        }
        //存储验证码
        foreach ($phone as $item) {
            SmsReport::create([
                'phone' => $item,
                'id_code' => $data['code'] ?? '',
                'msg' => $data,
                'user_id' => request()->user_id ?: 0,
                'status' => $return ? 1 : 0,
                'ip' => $data['ip'] ?? '',
                'return_data' => $return ? '' : substr($this->getError(), 0, 255),
                'sms_type' => $data['sms_type'] ?? 0
            ]);
        }
        //记录错误信息
        if (!$return) {
            return $this->error('发送失败，请联系管理员');
        }
        return true;
    }

    /**
     * 调用短信接口发送短信
     *
     * @param array $phone 电话号码
     * @param array $data 短信内容
     * @param string $templateCode 短信模板
     * @return boolean 返回调用成功消息 或 false
     */
    private function _post(array $phone, string $templateCode, array $data): bool
    {
        try {
            AlibabaCloud::accessKeyClient($this->config['key'], $this->config['secret'])
                ->regionId($this->config['region_id'])
                ->asDefaultClient();
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => 'cn-hangzhou',
                        'PhoneNumbers' => implode(',', $phone),
                        'SignName' => $this->config['sign_name'],
                        'TemplateCode' => $templateCode,
                        'TemplateParam' => json_encode($data),
                    ],
                ])
                ->request();
            $result = $result->toArray();
            if (isset($result['Code']) && $result['Code'] == 'OK') {
                return true;
            }
            return $this->error($result['Message'] ?? '');
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->error_code = 1;
        }
        return false;
    }

    /**
     * 检查发送场景
     *
     * @param array $phones
     * @param string $ip
     * @param boolean $isAdmin 是否管理员
     * @return boolean
     */
    protected function checkSend(array $phones, string $ip, bool $isAdmin = false): bool
    {
        if (count($phones) > 1 || $isAdmin || in_array($phones[0], Sms::$whiteList)) {
            return true;
        }
        //限制发送频率为20秒/次
        $postTime = date('Y-m-d H:i:s', time() - 20);
        $num = SmsReport::where([['phone', '=', $phones[0]], ['created_at', '>', $postTime]])->count();
        if ($num > 0) {
            return $this->error('发送太频繁，请稍后再试');
        }

        if (isOnline()) {
            // 限制单天最大发送次数为20次
            $postTime = date('Y-m-d H:i:s', time() - 86400);
            $num = SmsReport::where([['phone', '=', $phones[0]], ['created_at', '>', $postTime]])->count();
            if ($num >= $this->day_max_count) {
                return $this->error('发送次数过多，请稍后再试');
            }

            // 限制单一ip最大发送次数为20次
            $num = SmsReport::where([['ip', '=', $ip], ['created_at', '>', $postTime]])->count();
            if ($num >= $this->day_max_count * 2) {
                return $this->error('发送次数过多，请稍后再试');
            }
        }
        return true;
    }

    protected function error($text): false
    {
        $this->error = $text;
        return false;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getErrorCode(): int
    {
        return $this->error_code;
    }
}
