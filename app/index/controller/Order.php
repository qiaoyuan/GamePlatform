<?php
namespace app\index\controller;

use app\common\model\QuestionnairesOrder;
use app\common\model\Snowflake;
use app\index\BaseController;
use Pay\Factory;
use think\App;

class Order extends BaseController
{

    public function create()
    {
        $param = $this->request->param();

        $time = time();
        $questionnairesObj = \app\common\model\Questionnaires::where(['id'=>$param['questionnaire_id'], 'status'=>1])->find();
        if(empty($questionnairesObj)) {
            $this->error('问卷不存在');
        }

        $order = QuestionnairesOrder::where(['uid', $this->getUid(), 'questionnaire_id', $param['questionnaire_id']])->find();
        if(empty($order)) {

            // 使用例子
            $snowflake = new Snowflake(1, 1);
            $orderId = $snowflake->nextId();

            $orderInfo = [
                'questionnaire_id'=>$param['questionnaire_id'],
                'uid'=>$this->getUid(),
                'created_at'=> $time,
                'input_data' => json_encode($param),
                'price' => $questionnairesObj->price,
                'pay_status' => QuestionnairesOrder::PAY_UNPAID_STATUS,
            ];
            $order = QuestionnairesOrder::create($orderInfo);

        }

        $this->success('创建订单成功', ['info'=>$order]);

    }


}
