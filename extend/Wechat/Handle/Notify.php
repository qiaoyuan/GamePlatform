<?php
namespace Wechat\Handle;

use EasyWeChat\OfficialAccount\Application;
use Wechat\Factory;

class Notify
{
    private Application $app;

    const TYPE_WARNING = 1;

    public static array $templates = [
        self::TYPE_WARNING => 'PQwJ0jZA_YbcOUdvcqHIo87L46mcgZrwxyxE3hv_Lyk',
    ];

    public static array $warningGroup = [
        'trans' => [
        ]
    ];

    public function __construct()
    {
        $this->app = Factory::mp();
    }

    public function send($openid, $type, $data, $url = null)
    {
        try {
            $params = [
                'touser' => $openid,
                'template_id' => self::$templates[$type],
                //'url' => (is_array($url) || !$url) ? getFullDomain('m') : $url,
                'data' => $data
            ];
            if (is_array($url)) {
                $params['miniprogram'] = $url;
            }
            if (env('app_status')) {
                return false;
            }
            $r = $this->app->template_message->send($params);
            trace($r, 'wechat_notify');
            return $r;
        } catch (\Exception $e) {
            trace($e->__toString(), 'wechat_notify');
        }
        return false;
    }

    public function warning($openid, $title, $content, $remark = '', $url = '')
    {
        is_array($openid) || $openid = [$openid];
        foreach ($openid as $item) {
            $this->send($item, self::TYPE_WARNING, [
                'first' => $title,
                'keyword1' => $content,
                'keyword2' => dateNow(), //
                'remark' => $remark ?: '详情，请点击查看'
            ], $url);
        }
    }

    public static function warning404($goodsId, $src)
    {
    }

    public static function transWarning($site, $error, $where)
    {
    }
}