<?php

namespace app\index\controller;

use app\common\model\QuestionAnswer;
use app\common\model\QuestionAnswers;
use app\common\model\QuestionResponse;
use app\index\BaseController;

class User extends BaseController
{

    public function login()
    {

        $param = $this->request->param();
        $content = json_encode($param);

        //判断是否是老用户
        $usrObj = \app\common\model\User::where('open_id', $param['code'])->find();

        //新用户注册
        if(empty($usrObj)) {

            $data = [
                'username' => '',
//                'created_at' => time(),
                'nickname' => '',
                'open_id' => $param['code'],
                'content' => $content,
            ];
            $usrObj = \app\common\model\User::create($data);

        }

        $token = \app\common\model\User::getToken($param['code'], $usrObj->id);

        \app\common\model\User::where('open_id', $param['code'])->update(['token'=>$token]);

        //然后直接返回token
        $this->success('登录成功', ['token' => $token, 'open_id' => $param['code']]);
    }

    //用户数据
    public function info()
    {
        $uid = $this->request->uid;
        $this->success('', ['info'=>\app\common\model\User::find($uid)]);

    }

    //创建报告
    public function createRes()
    {
        $uid = $this->getUid();

        $param = $this->request->param();

        //['options'=>[
        // 'so'
        //]]

//        $param['options'] = [
//            [
//                'score' => 1,
//                'question_id' => 2,
//            ],
//
//            [
//                'score' => 3,
//                'question_id' => 2,
//            ],
//
//            [
//                'score' => 5,
//                'question_id' => 6,
//            ],
//
//            [
//                'score' => 2,
//                'question_id' => 7,
//            ],
//
//            [
//                'score' => 4,
//                'question_id' => 8,
//            ],
//
//            [
//                'score' => 5,
//                'question_id' => 9,
//            ],
//
//            [
//                'score' => 3,
//                'question_id' => 18,
//            ],
//
//            [
//                'score' => 2,
//                'question_id' => 20,
//            ],
//        ];
        if (empty($param['options']) || empty($param['questionnaire_id'])) {
            $this->error("参数缺少");
        }

        $scoreSum = array_sum(array_column($param['options'], 'score'));
        if($scoreSum == 0) {
            $this->error("数据提异常");
        }

        $where = [
            ['start', '<=', $scoreSum],
            ['lt', '>=', $scoreSum],
            ['questionnaire_id', '=', $param['questionnaire_id']],
        ];
        $questionResponse = QuestionResponse::where($where)->find();
        if (empty($questionResponse)) {
            $this->error("联系管理员,生成报告异常");
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
                $this->success("请勿重复生成问卷报!", ['info' => $questionAnswer]);
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

        $this->success("生成报告成功", ['info' => $questionAnswer]);

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

    public function report() {
        $param = $this->request->param();

        if(empty($param['id'])) {
            $this->error("参数异常!");
        }
        $id = QuestionAnswer::where('id', $param['id'])->value('response_id');
        if(empty($id)) {
            $this->error("报告不存在");
        }
        $res = QuestionResponse::where('id', $id)->find();
        $this->success("", ['info' => $res]);

    }


}
