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

        $time = time();

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

//            $config = [
//                'douyin' => [
//                    'default' => [
//                    'mch_id' => config('douyin.mch_id'),
//                    'mch_secret_token' => config('douyin.mch_secret_token'),
//                    'mch_secret_salt' => config('douyin.mch_secret_salt'),
//                    'mini_app_id' => config('douyin.mini_app_id'),
//                    'thirdparty_id' => '',
//                    'notify_url' => config('douyin.notify_url'),
//                    ],
//                ],
//            ];
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
                    'file' => './logs/pay.log',
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

//            var_dump($this->pay(1, $questionnairesObj->title,'body', $order->order_id));

            \Yansongda\Pay\Pay::config($config);
            $no = date('YmdHis').rand(1000, 9999);
            $orderInput = [
                'out_order_no' => $order->order_id.'',
//                'out_order_no' => date('YmdHis').rand(1000, 9999),
                'total_amount' => 100,
                'subject' => "qy - test - subject - 01",
                'body' => "qy - test - body - 01",
                'valid_time' => 600,
            ];

            $result =  \Yansongda\Pay\Pay::douyin()->mini($orderInput);

            $order->pay_extent = json_encode($result);
            $order->save();

            $this->success('创建订单成功', ['info' => $result, 'pay_data' => $config ]);

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
        QuestionnairesOrderCallback::create(['input_data' => json_encode($input), 'created_at' => time()]);

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

    //amount金额  subject标题 body详情  out_trade_no订单号  notify_url回调地址
    public function pay($amount,$subject,$body,$out_trade_no)
    {
        $site=config('douyin');

        if($amount<=0){
            $this->error(__('金额不对'));
        }

        $amount=$amount*100;
        $url = 'https://developer.toutiao.com/api/apps/ecpay/v1/create_order';
        $data = [
            "app_id" => $site['douyin']['appid'],
            "out_order_no" =>$out_trade_no,
            "total_amount" => $amount,
            "subject" => $subject,
            "body" => $body,
            "valid_time" => 180,
            "cp_extra" =>$subject,
            "notify_url" => config('douyin.notify_url'),
        ];
        $data['sign']= $this->sign($data,$site['douyin']['salt']);
        $res= $this->jsonPost($url,$data);
        $res=json_decode($res,true);
        if(!is_array($res)){
            $this->error($res);
        }
        if($res['err_no']!=0){
            $this->error($res['err_tips']);
        }
        $payData=$res['data'];
        $this->success('订单提交成功 正在跳转支付',$payData);
    }


    //支付签名
    function sign($map,$salt) {
        $rList = [];
        foreach($map as $k =>$v) {
            if ($k == "other_settle_params" || $k == "app_id" || $k == "sign" || $k == "thirdparty_id")
                continue;

            $value = trim(strval($v));
            if (is_array($v)) {
                $value = arrayToStr($v);
            }

            $len = strlen($value);
            if ($len > 1 && substr($value, 0,1)=="\"" && substr($value, $len-1)=="\"")
                $value = substr($value,1, $len-1);
            $value = trim($value);
            if ($value == "" || $value == "null")
                continue;
            $rList[] = $value;
        }
        $rList[] =$salt;
        sort($rList, SORT_STRING);
        return md5(implode('&', $rList));
    }


    function jsonPost($url, $postData, $customHeaders = []) {
        // 初始化curl
        $ch = curl_init($url);

        // 设置curl选项
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 注意：不推荐在生产环境中禁用SSL验证
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 返回结果而不是直接输出
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 跟随重定向
        curl_setopt($ch, CURLOPT_POST, true); // 发送POST请求
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData)); // JSON格式数据
        // 设置HTTP头
        curl_setopt($ch, CURLOPT_HTTPHEADER, $customHeaders);

        // 执行请求
        $response = curl_exec($ch);

        // 检查是否有错误发生
        if (curl_errno($ch)) {
            $error = 'Curl error: ' . curl_error($ch);
            curl_close($ch); // 关闭curl资源
            return $error; // 返回错误信息
        }

        curl_close($ch); // 关闭curl资源

        // 直接返回原始响应，不进行json_decode
        return $response;
    }

}
