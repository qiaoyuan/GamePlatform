<?php
namespace app\index\controller;

use app\common\model\QuestionnairesOrder;
use app\index\BaseController;

class Order extends BaseController
{

    public function create()
    {
        $param = $this->request->param();

        $order = QuestionnairesOrder::where(['uid', $this->getUid(), 'questionnaire_id', $param['questionnaire_id']])->find();
        if(!empty($order)) {
           $this->error('您已经购买过此问卷');
        }
        $time = time();
        $questionnairesObj = \app\common\model\Questionnaires::where(['id'=>$param['questionnaire_id'], 'status'=>1])->find();
        if(empty($questionnairesObj)) {
            $this->error('问卷不存在');
        }

        $orderInfo = [
            'questionnaire_id'=>$param['questionnaire_id'],
            'uid'=>$this->getUid(),
            'created_at'=> $time,
            'input_data' => json_encode($param),
            'price' => $questionnairesObj->price,
            'pay_status' => QuestionnairesOrder::PAY_UNPAID_STATUS,
        ];
        $obj = QuestionnairesOrder::create($orderInfo);

        $this->success('创建订单成功', $obj);
    }

    //回调支付
    public function callback()
    {
        $param = $this->request->param();
        $inputRes = json_encode($param);

    }

    /**
     * 预付订单
     */
    public function pevOrder()
    {

    }
}
