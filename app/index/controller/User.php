<?php
namespace app\index\controller;

use app\common\model\QuestionAnswer;
use app\common\model\QuestionAnswers;
use app\common\model\QuestionResponse;
use app\index\BaseController;

class User extends BaseController
{

    public function register()
    {

        $this->success('注册成功', []);
    }

    //用户数据
    public  function info() {

        $this->success('', []);
    }

    //创建报告
    public function createRes()
    {
        $param = $this->request->param();
        if(empty($param['options']) || $param['questionnaire_id']) {
            $this->error("参数缺少");
        }
        $score = array_sum($param['options']);

        $where = [
            ['start', '>=', $score],
            ['lt', '<=', $score],
        ];
        $questionResponse = QuestionResponse::where($where)->limit(1)->find();
        if($questionResponse->isEmpty()) {
            $this->error("生成报告异常");
        }

        $questionAnswer = [
            'json'=>json_encode($param['options']),
            'created_at'=>time(),
            'uid'=>999,
            'questionnaire_id'=>$param['questionnaire_id'],
            'score'=>$param['score'],
            'response_id' => $questionResponse->id
        ];
        $questionAnswer = QuestionAnswer::create($questionAnswer);
        if($questionAnswer->isEmpty()) {
            $this->error("生成报告失败");
        }

        $this->success("生成报告成功", ['res'=>$questionResponse]);
    }

    /**
     * 获取报告
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function reportList()
    {
        $param = $this->request->param();

        $uid = 999;

        $questionAnswer = QuestionAnswer::where('uid', $uid)->with('questionnaire')->select();
        $this->success("", ['list'=>$questionAnswer]);
    }

}
