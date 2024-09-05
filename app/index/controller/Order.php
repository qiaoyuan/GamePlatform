<?php

namespace app\index\controller;

use app\common\model\QuestionAnswer;
use app\common\model\QuestionnairesOrder;
use app\common\model\QuestionnairesOrderCallback;
use app\common\model\Snowflake;
use app\index\BaseController;
use EasyWeChat\Factory;

class Order extends BaseController
{

    public function create()
    {
        $param = $this->request->param();


        $questionnairesObj = \app\common\model\Questionnaires::where(['id' => $param['questionnaire_id'], 'status' => 1])->find();
        if (empty($questionnairesObj)) {
            $this->error('问卷不存在');
        }

        $order = QuestionnairesOrder::where(['uid' => $this->getUid(), 'questionnaire_id' => $param['questionnaire_id'], 'status' => 1])->find();


        if (!empty($order) && $order->price != $questionnairesObj->price) {
            $order->status = 0;
            $order->save();
            $order = null;
        }

        if (!empty($order)) {


            if ($order->pay_status == QuestionnairesOrder::PAY_PAID_STATUS) {

                $answerObj = QuestionAnswer::where(['uid' => $this->getUid(), 'questionnaire_id' => $param['questionnaire_id']])->find();
                if (!empty($answerObj) && $answerObj->response_id > 0) {
                    $this->error('已支付', ['info' => $answerObj], 3001);
                }
                $this->error('已支付未生成报告', [], 3002);
            }

        } else {


            // 使用例子
            $snowflake = new Snowflake(1, 1);
            $orderId = $snowflake->nextId();

            $orderInfo = [
                'questionnaire_id' => $param['questionnaire_id'],
                'uid' => $this->getUid(),
                'input_data' => json_encode($param),
                'price' => $questionnairesObj->price,
                'pay_status' => QuestionnairesOrder::PAY_UNPAID_STATUS,
                'order_id' => $orderId,
            ];

            $order = QuestionnairesOrder::create($orderInfo);

        }


        if ($this->getPlatform() == \app\common\model\User::DOUYIN_PLATFORM) {

            $config = [
                'douyin' => [
                    'default' => [
                        'mch_id' => config('douyin.mch_id'),
                        'mch_secret_token' => config('douyin.mch_secret_token'),
                        'mch_secret_salt' => config('douyin.mch_secret_salt'),
                        'mini_app_id' => config('douyin.mini_app_id'),
                        'thirdparty_id' => '',
                        'notify_url' => config('douyin.notify_url'),
                    ],
                ],
                'logger' => [
                    'enable' => false,
                    'file' => './paylogs/pay.log',
                    'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                    'type' => 'single', // optional, 可选 daily.
                    'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
                ],
            ];

            \Yansongda\Pay\Pay::config($config);
            $orderInput = [
                'out_order_no' => $order->order_id.'',
                'total_amount' => $questionnairesObj->price * 100,
                'subject' => $questionnairesObj->title,
                'body' => "qy - test - body - 01",
                'valid_time' => 600,
            ];

            $result =  \Yansongda\Pay\Pay::douyin()->mini($orderInput);

            $order->pay_extent = json_encode($result);
            $order->save();

            $this->success('创建订单成功', ['info' => $result ]);

        } else {

            $config = [
                // 必要配置
                'app_id' => config('wechat.app_id'),
                'mch_id' => config('wechat.mch_id'),
                'key' => config('wechat.key'),   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
//            'cert_path'          => app()->getRootPath().'public/cert.pem',
//            'key_path'           => app()->getRootPath().'public/cert.pem',
                'notify_url' => config('wechat.notify_url'),     // 你也可以在下单时单独设置来想覆盖它
            ];

            $app = Factory::payment($config);

            $payData = [
                'body' => $questionnairesObj->title,
                'out_trade_no' => $order->order_id,
                'total_fee' => $questionnairesObj->price * 100,
                'notify_url' => config('wechat.notify_url'),
                'trade_type' => 'JSAPI',
                'openid' => $this->getOpenId(),
            ];
            $res = $app->order->unify($payData);

            if ($res['return_code'] == 'SUCCESS') {
                if (empty($res['prepay_id'])) {
//                $this->error('支付异常：', ['info'=>$res]);
                    $this->error('已支付未生成报告', [], 3002);
                }
            }

            $appId = $res['appid'];
            $nonceStr = $res['nonce_str'];
            $package = 'prepay_id=' . $res['prepay_id'];
            $signType = 'MD5';
            $timeStamp = time() . '';
            $key = config('wechat.key');

            $string = "appId=$appId&nonceStr=$nonceStr&package=$package&signType=$signType&timeStamp=$timeStamp&key=$key";

            $paySign = strtoupper(md5($string));
            $res['paySign'] = $paySign;
            $res['timeStamp'] = $timeStamp;
            $res['package'] = $package;

            $order->pay_extent = json_encode($res);
            $order->save();

            $this->success('创建订单成功', ['info' => $res, 'pay_data' => $payData]);

        }

    }


    //回调支付
    public function callback()
    {
        $input['head'] = $this->request->header();
        $input['param'] = $this->request->param();
        QuestionnairesOrderCallback::create(['input_data' => json_encode($input), 'created_at' => time(), 'platform'=>1]);


        $config = [
            // 必要配置
            'app_id' => config('wechat.app_id'),
            'mch_id' => config('wechat.mch_id'),
            'key' => config('wechat.key'),   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
//            'cert_path'          => 'path/to/your/cert.pem',
//            'key_path'           => 'path/to/your/key',
            'notify_url' => config('wechat.notify_url'),     // 你也可以在下单时单独设置来想覆盖它
        ];

        $app = Factory::payment($config);

        $res = $app->handlePaidNotify(function ($message, $fail) {
            $order = $message['out_trade_no'];
            $orderObj = QuestionnairesOrder::where('order_id', $order)->find();
            $orderObj->pay_status = QuestionnairesOrder::PAY_PAID_STATUS;
            $orderObj->save();

            $answer = QuestionAnswer::where('uid', $orderObj->uid)->where('questionnaire_id', $orderObj->questionnaire_id)->find();
            if (empty($answer)) {
                $questionAnswer = [
                    'json' => '',
                    'uid' => $orderObj->uid,
                    'questionnaire_id' => $orderObj->questionnaire_id,
                    'score' => 0,
                    'response_id' => 0,
                ];
                QuestionAnswer::create($questionAnswer);
            }

            return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了

        });

        return $res;

    }



    //回调支付
    public function dycallback()
    {
        $input['head'] = $this->request->header();
        $input['param'] = $this->request->param();
        $calObj = QuestionnairesOrderCallback::create(['input_data' => json_encode($input), 'created_at' => time(), 'platform'=>2]);

        $config = [
            'douyin' => [
                'default' => [
                    'mch_id' => config('douyin.mch_id'),
                    'mch_secret_token' => config('douyin.mch_secret_token'),
                    'mch_secret_salt' => config('douyin.mch_secret_salt'),
                    'mini_app_id' => config('douyin.mini_app_id'),
                    'thirdparty_id' => '',
                    'notify_url' => config('douyin.notify_url'),
                ],
            ],
            'logger' => [
                'enable' => false,
                'file' => './paylogs/pay.log',
                'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
                'type' => 'single', // optional, 可选 daily.
                'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
            ],
//                'http' => [ // optional
//                    'timeout' => 5.0,
//                    'connect_timeout' => 5.0,
//                    // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
//                ],
        ];

        \Yansongda\Pay\Pay::config($config);


        // 是的，你没有看错，就是这么简单！
        $result = \Yansongda\Pay\Pay::douyin()->callback();
        $calObj->res = json_encode($result);
        $calObj->save();
        if($result['type'] == 'payment') {
            $orderRes = json_decode($result['msg'], true);
            $orderObj = QuestionnairesOrder::where('order_id', $orderRes['cp_orderno'])->find();
            $orderObj->pay_status = QuestionnairesOrder::PAY_PAID_STATUS;
            $orderObj->save();
        }

        return \Yansongda\Pay\Pay::douyin()->success();

    }

}
