<?php

namespace app\index\controller;

use app\common\model\QuestionAnswer;
use app\common\model\QuestionnairesOrder;
use app\common\model\QuestionnairesOrderCallback;
use app\common\model\Snowflake;
use app\index\BaseController;
use EasyWeChat\Factory;
use GuzzleHttp\Client;

class Order extends BaseController
{
    public $client = null;

    public function initialize()
    {
        $this->client = new Client([
            'timeout' => 30,
            'base_uri' => 'https://developer.toutiao.com/'
        ]);
    }

    public function create()
    {
        $param = $this->request->param();


        $questionnairesObj = \app\common\model\Questionnaires::where(['id' => $param['questionnaire_id'], 'status' => 1])->find();
        if (empty($questionnairesObj)) {
            $this->error('问卷不存在');
        }

        if (!empty($param['order_timeout'])) {
            $order = QuestionnairesOrder::where('order_id', $param['order_timeout'])->find();
            if(!empty($order)) {
                $order->status = 0;
                $order->remark = '订单超时.';
                $order->save();
                $order = null;
            }

        } else {

            $order = QuestionnairesOrder::where(['uid' => $this->getUid(), 'questionnaire_id' => $param['questionnaire_id'], 'status' => 1])->find();
        }


        if (!empty($order) && $order->price != $questionnairesObj->price) {
            $order->status = 0;
            $order->save();
            $order->remark = '产品价格改变.';
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
                'out_order_no' => $order->order_id . '',
                'total_amount' => $questionnairesObj->price * 100,
                'subject' => $questionnairesObj->title,
                'body' => "qy - test - body - 01",
                'valid_time' => 600,
            ];

            $result = \Yansongda\Pay\Pay::douyin()->mini($orderInput);
            $order->pay_extent = json_encode($result);
            $order->save();

            $result['self_order'] = $order->order_id.'';
            $this->success('创建订单成功', ['info' => $result]);

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
            $result['self_order'] = $order->order_id.'';
            $this->success('创建订单成功', ['info' => $res, 'pay_data' => $payData]);

        }

    }


    //回调支付
    public function callback()
    {
        $input['head'] = $this->request->header();
        $input['param'] = $this->request->param();
        QuestionnairesOrderCallback::create(['input_data' => json_encode($input), 'created_at' => time(), 'platform' => 1]);


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
            $orderObj->pay_type = QuestionnairesOrder::PAY_TYPE_WX;
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
        $calObj = QuestionnairesOrderCallback::create(['input_data' => json_encode($input), 'created_at' => time(), 'platform' => 2]);

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
        if ($result['type'] == 'payment') {
            $orderRes = json_decode($result['msg'], true);
            $orderObj = QuestionnairesOrder::where('order_id', $orderRes['cp_orderno'])->find();
            $orderObj->pay_status = QuestionnairesOrder::PAY_PAID_STATUS;

            if ($orderRes['way'] == "1") {
                $orderObj->pay_type = QuestionnairesOrder::PAY_TYPE_DY_WX;
            } else if ($orderRes['way'] == "2") {
                $orderObj->pay_type = QuestionnairesOrder::PAY_TYPE_DY_ALIPAY;
            } else {
                $orderObj->pay_type = QuestionnairesOrder::PAY_TYPE_DY_PAY;
            }
            $orderObj->save();

            $order = $orderObj->toArray();
            $goods = \app\common\model\Questionnaires::find($orderObj->questionnaire_id)->toArray();
            $openId = \app\common\model\User::find($order['uid'])->open_id;
            $this->pushOrder($goods, $order, $openId);

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
        }

        return \Yansongda\Pay\Pay::douyin()->success();

    }

    /**
     * 订单推送到抖音
     * @param $data array 订单数据
     * @note order_status 与 status须保持一致,但类型不同
     * @return array
     */
    public function pushOrder($goods, $order, $openId)
    {
        $data = [];//获取订单信息
        $api = "api/apps/order/v2/push";
        $openid = '';//获取下单用户openid
        //组装商品
        $item_list[] = [
            'item_code' => '1001' . $goods['id'],
            'img' => $goods['img_url'],
            'title' => $goods['title'],
            'amount' => 1,
            'price' => (int)($goods['price'] * 100),

        ];//参数对应请查看官方文档，注意字段类型
        // 组装订单
        $orderDetail = [
            'order_id' => $order['order_id'] . '', 'create_time' => strtotime($order['created_at']), 'status' => '已支付', 'amount' => 1,
            'total_price' => (int)($order['price'] * 100), 'detail_url' => "pages/order/orderDetail?id=" . $order['order_id'], 'item_list' => $item_list];

        //{\"detail_url\":\"https://www.xxxx.com/shop/order/orderDetail?orderId=21000240218164635217330&pad_check=df126473398e4840111ba0c620ca1c5c\",\"amount\":2,\"create_time\":1708245997095,\"total_price\":2,\"item_list\":[{\"amount\":1,\"img\":\"https://www.xxxx.com/resources/2063/1915501.jpg\",\"price\":1,\"title\":\"字节小程序语音版\"},{\"amount\":1,\"img\":\"https://www.xxxx.com/resources/2063/19155_01.jpg\",\"price\":1,\"title\":\"主卡\"}],\"order_id\":\"21000240218164635217330\",\"status\":\"订单已完成\"}
        $param = ['access_token' => $this->getAccessTokens(), 'app_name' => "douyin",
            'open_id' => $openId, 'update_time' => $this->getMillisecond(), 'order_detail' => json_encode($orderDetail, JSON_UNESCAPED_UNICODE), 'order_type' => 0, 'order_status' => 1];

        $body = $this->client->post($api, ['json' => $param]);
        $result = json_decode($body->getBody()->getContents(), true);

        if (isset($result['err_code']) && $result['err_code'] === 0) {
            return 0;
        }
        return -1;


    }

    /**
     * 获取AccessToken
     */
    public function getAccessTokens()
    {
        $api = "api/apps/v2/token";
        $param = ['appid' => config('douyin.mini_app_id'), 'secret' => config('douyin.secret'), 'grant_type' => "client_credential"];
        $body = $this->client->post($api, ['json' => $param]);
        $res = $body->getBody()->getContents();
        $data = json_decode($res, true);
        if ($data['err_no'] == 0) {
            $access_token = $data['data']['access_token'];
        }
        return $access_token;
    }

    public function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    public function test()
    {

        $order = QuestionnairesOrder::find(160)->toArray();
        $goods = \app\common\model\Questionnaires::find(32)->toArray();
        $openId = \app\common\model\User::find($order['uid'])->open_id;
        echo $this->pushOrder($goods, $order, $openId);
        die;
    }

    public function get()
    {
        $param = $this->request->param();
        if(empty($param['id']) ) {
            $this->error('订单id不能为空');
        }

        $order = QuestionnairesOrder::find($param['id'])->toArray();
        $order['title'] = \app\common\model\Questionnaires::find($order['questionnaire_id'])->title;
        $order['pay_type'] = QuestionnairesOrder::$PAY_TYPE_MAP[$order['pay_type']];
        unset($order['input_data']);
        $this->success('订单详情', ['info' => $order]);

    }

}
