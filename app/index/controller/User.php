<?php

namespace app\index\controller;

use app\admin\controller\Config;
use app\common\model\QuestionAnswer;
use app\common\model\QuestionAnswers;
use app\common\model\QuestionResponse;
use app\index\BaseController;
use EasyWeChat\Factory;

class User extends BaseController
{

    public function login()
    {

        $param = $this->request->param();
        $content = json_encode($param);

        //判断是否是老用户
        $config = [
            'app_id' => config('wechat.app_id'),
            'secret' => config('wechat.secret'),

            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',

            'log' => [
                'level' => 'debug',
                'file' => __DIR__ . '/wechat.log',
            ],
        ];

        try {

            $app = Factory::miniProgram($config);

            $res = $app->auth->session($param['code']);

        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
//        $res = ['openid'=>'oJ6ds7ZAo-Lgjc8987HC7QcWuNEI'];

        //判断是否是老用户
        $openId = $res['openid'];
        $usrObj = \app\common\model\User::where('open_id', $openId)->find();

        //新用户注册
        if (empty($usrObj)) {

            $data = [
                'username' => '',
                'nickname' => '',
                'open_id' => $openId,
                'content' => $content,
            ];
            $usrObj = \app\common\model\User::create($data);

        }

        $token = \app\common\model\User::getToken($openId, $usrObj->id);
//        $user = \app\common\model\User::verifyToken($this->request->header(config('jwt.field'), ''));
//        var_dump($user);


        \app\common\model\User::where('open_id', $openId)->update(['token' => $token]);

        //然后直接返回token
        $this->success('登录成功', ['token' => $token, 'open_id' => $openId] );

    }

    //用户数据
    public function info()
    {
        $uid = $this->request->uid;
        $this->success('', ['info' => \app\common\model\User::find($uid)]);

    }

    //创建报告
    public function createRes()
    {
        $uid = $this->getUid();

        $param = $this->request->param();

        if (empty($param['options']) || empty($param['questionnaire_id'])) {
            $this->error("参数缺少");
        }

        $scoreSum = array_sum(array_column($param['options'], 'score'));
        if ($scoreSum == 0) {
            $this->error("提交数据异常");
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
        if (!empty($questionAnswer)) {

            //2、已完成报告
            if ($questionAnswer->response_id == $questionResponse->id) {
                $this->success("请勿重复生成问卷报!", ['info' => $questionAnswer]);
            }

            //1、存在未完成报告
            $questionAnswer->score = $scoreSum;
            $questionAnswer->response_id = $questionResponse->id;
            $questionAnswer->updated_at = time();
            $questionAnswer->save();

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
        if (!empty($param['is_ok'])) {
            if ($param['is_ok'] == 2) {
                $order = 'id desc';


            } else if ($param['is_ok'] == 1) {
                $where[] = ['response_id', '>', 0];
            }
        }

        $questionAnswer = QuestionAnswer::where($where)->with(['questionnaire' => function ($query) {
            $query->with(['articleCategorys']);
        }])->select();
        $this->success("", ['list' => $questionAnswer]);
    }

    public function report()
    {
        $param = $this->request->param();

        if (empty($param['id'])) {
            $this->error("参数异常!");
        }
        $id = QuestionAnswer::where('id', $param['id'])->value('response_id');
        if (empty($id)) {
            $this->error("报告不存在");
        }
        $res = QuestionResponse::where('id', $id)->find();
        $this->success("", ['info' => $res]);

    }


}
