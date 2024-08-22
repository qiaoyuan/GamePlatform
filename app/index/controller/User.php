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
    public function info()
    {

        $this->success('', []);
    }

    //创建报告
    public function createRes()
    {
        $uid = $this->getUid();

        $param = $this->request->param();

        //['options'=>[
        // 'so'
        //]]

        $param['options'] = [
            [
                'score' => 1,
                'question_id' => 2,
            ],

            [
                'score' => 3,
                'question_id' => 2,
            ],

            [
                'score' => 5,
                'question_id' => 6,
            ],

            [
                'score' => 2,
                'question_id' => 7,
            ],

            [
                'score' => 4,
                'question_id' => 8,
            ],

            [
                'score' => 5,
                'question_id' => 9,
            ],

            [
                'score' => 3,
                'question_id' => 18,
            ],

            [
                'score' => 2,
                'question_id' => 20,
            ],
        ];
        if (empty($param['options']) || empty($param['questionnaire_id'])) {
            $this->error("参数缺少");
        }
        $scoreSum = array_sum(array_column($param['options'], 'score'));

        $where = [
            ['start', '<=', $scoreSum],
            ['lt', '>=', $scoreSum],
            ['questionnaire_id', '=', 13],
        ];
        $questionResponse = QuestionResponse::where($where)->find();
        if (empty($questionResponse)) {
            $this->error("生成报告异常");
        }


        //去重判断
        $where = [
            'uid' => $uid,
            'questionnaire_id' => $param['questionnaire_id'],
        ];
        $questionAnswer = QuestionAnswer::where($where)->find();
        //判断1、存在未完成报告 2、已完成报告 3、用户没有该问卷报告
        if (!empty($questionAnswer) ) {

            //2、已完成报告
            if($questionAnswer->response_id = $questionResponse->id) {
                $this->success("请勿重复生成问卷报!", ['res' => $questionResponse]);
            }

            //1、存在未完成报告
            $questionAnswer = [
                'json' => json_encode($param['options']),
                'created_at' => time(),
                'uid' => $this->getUid(),
                'questionnaire_id' => $param['questionnaire_id'],
                'score' => $scoreSum,
                'response_id' => $questionResponse->id
            ];

            QuestionAnswer::update(['response_id' => $questionResponse->id], ['id' => $questionAnswer->id]);

        } else { //3 用户没有该问卷报告

            $questionAnswer = [
                'json' => json_encode($param['options']),
                'created_at' => time(),
                'uid' => $this->getUid(),
                'questionnaire_id' => $param['questionnaire_id'],
                'score' => $scoreSum,
                'response_id' => $questionResponse->id
            ];
            $questionAnswer = QuestionAnswer::create($questionAnswer);
            if ($questionAnswer->isEmpty()) {
                $this->error("生成报告失败");
            }

        }

        $this->success("生成报告成功", ['res' => $questionResponse]);

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

        $where[] = ['uid', '=', $this->getUid()];

        //未完成报告
        if(!empty($param['is_ok']) ) {
            if($param['is_ok'] === 0) {
                $where[] = ['response_id', '=', 0];
            } else if($param['is_ok'] === 1) {
                $where[] = ['response_id', '>', 0];
            }
        }


        $questionAnswer = QuestionAnswer::where($where)->with(['questionnaire' => function ($query) {
            $query->with(['articleCategorys']);
        }])->select();
        $this->success("", ['list' => $questionAnswer]);
    }

}
