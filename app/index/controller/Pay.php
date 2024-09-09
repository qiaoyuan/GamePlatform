<?php

namespace app\index\controller;

use app\common\model\QuestionnairesOrder;
use app\index\BaseController;
use Pay\Factory;
use think\App;

class Pay extends BaseController
{
    public App $app;


    public function getApp()
    {
        $config = [
            // 必要配置
            'app_id' => config('wechat.app_id'),
            'mch_id' => config('wechat.mch_id'),
            'key' => config('wechat.key'),   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
//            'cert_path'          => 'path/to/your/cert.pem', // XXX: 绝对路径！！！！
//            'key_path'           => 'path/to/your/key',      // XXX: 绝对路径！！！！
            'notify_url' => config('wechat.notify_url'),     // 你也可以在下单时单独设置来想覆盖它
        ];

        $app = Factory::payment($config);
        return $app;

//        $app->order->unify([
//            'body' => '支付测试',
//            'out_trade_no' => '2018090312345678',
//            'total_fee' => 1,
//            'notify_url' => 'http://requestbin.net/r/1zjqj1z1', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
//            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
//            'openid' => 'oUpF8uMuAJO_M2pxb1Q9zNjWeS6o',
//        ]);

    }


    //回调支付
    public function callback()
    {
        $this->getApp()->handlePaidNotify(function ($message, $fail) {
            $order = $message['out_trade_no'];
            $orderObj = QuestionnairesOrder::where('id', $order)->order();
            if (!$order || $order->paid_at) { // 如果订单不存在 或者 订单已经支付过了
                return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了
            }


        });

//        $response = $this->getApp()->hVjj(function ($message, $fail) {
//            // 你的逻辑
//            return true;
//            // 或者错误消息
//            $fail('Order not exists.');
//        });
//        $response->send();

    }

}
