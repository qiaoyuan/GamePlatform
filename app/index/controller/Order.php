<?php

namespace app\index\controller;

use app\common\model\QuestionAnswer;
use app\common\model\QuestionnairesOrder;
use app\common\model\Snowflake;
use app\index\BaseController;
use EasyWeChat\Factory;
use think\App;

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

        $order = QuestionnairesOrder::where(['uid' => $this->getUid(), 'questionnaire_id' => $param['questionnaire_id']])->find();
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
                'created_at' => $time,
                'updated_at' => $time,
                'input_data' => json_encode($param),
                'price' => $questionnairesObj->price,
                'pay_status' => QuestionnairesOrder::PAY_UNPAID_STATUS,
                'order_id' => $orderId,
            ];

            $order = QuestionnairesOrder::create($orderInfo);

        }

        $config = [
            // 必要配置
            'app_id'             => config('wechat.app_id'),
            'mch_id'             => config('wechat.mch_id'),
            'key'                => config('wechat.secret'),   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
//            'cert_path'          => app()->getRootPath().'public/cert.pem',
//            'key_path'           => app()->getRootPath().'public/cert.pem',
            'notify_url'         => config('wechat.notify_url'),     // 你也可以在下单时单独设置来想覆盖它
        ];

//        var_dump($config);

        $app = Factory::payment($config);

        $payData = [
            'body' => '问卷支付',
            'out_trade_no' => $order->order_id,
            'total_fee' => $questionnairesObj->price * 100,
            'notify_url' => config('wechat.notify_url'),
            'trade_type' => 'JSAPI',
            'openid' => $this->getOpenId(),
        ];
//        var_dump($payData);
//        die;
        $res = $app->order->unify($payData);
//        var_dump($res);
//        die;

        $this->success('创建订单成功', ['info' => $res, 'pay_data'=>$payData, 'config'=>$config]);

    }



    //回调支付
    public function callback()
    {
        $config = [
            // 必要配置
            'app_id'             => config('wechat.app_id'),
            'mch_id'             => config('wechat.mch_id'),
            'key'                => config('wechat.secret'),   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)
//            'cert_path'          => 'path/to/your/cert.pem',
//            'key_path'           => 'path/to/your/key',
            'notify_url'         => config('wechat.notify_url'),     // 你也可以在下单时单独设置来想覆盖它
        ];
        $app = Factory::payment($config);
        $app->handlePaidNotify(function ($message, $fail) {
            $order = $message['out_trade_no'];
            $orderObj = QuestionnairesOrder::where('id', $order)->find();
            if (!empty($orderObj)) { // 如果订单不存在 或者 订单已经支付过了
                QuestionnairesOrder::where('id', $order)->update(["pay_status"=>QuestionnairesOrder::PAY_PAID_STATUS]);
            }

            return true; // 告诉微信，我已经处理完了，订单没找到，别再通知我了


        });

    }

}
