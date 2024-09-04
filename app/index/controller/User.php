<?php

namespace app\index\controller;

use app\admin\controller\Config;
use app\common\model\QuestionAnswer;
use app\common\model\QuestionAnswers;
use app\common\model\QuestionResponse;
use app\index\BaseController;
use EasyWeChat\Factory;
use GuzzleHttp\Client;

class User extends BaseController
{

    private Client $client;


    public function login()
    {

        $param = $this->request->param();
        $param['platform'] = $param['platform'] ?? 1;

        $content = json_encode($param);

        if ($param['platform'] == 1) {
            //判断是否是老用户
            $config = [
                'app_id' => config('wechat.app_id'),
                'secret' => config('wechat.secret'),
                'response_type' => 'array',
                'log' => [
                    'level' => 'debug',
                    'file' => __DIR__ . '/wechat.log',
                ],
            ];

            if (empty($param['code'])) {
                $this->error('code参数必传');
            }

            try {

                $app = Factory::miniProgram($config);
                $res = $app->auth->session($param['code']);

            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }

            //判断是否是老用户
            $openId = $res['openid'];

        } else {

            $appId = config('douyin.app_id'); // 替换为你的应用ID
            $appSecret = config('douyin.secret'); // 替换为你的应用密钥

            $this->client = new Client([
                'timeout' => 30,
                'verify' => false,
                'base_uri' => 'https://open.douyin.com'
            ]);

            $body = [
                'code' => $param['code'], // 用户授权码，用户同意授权后，开发者可获取
                'client_key' => $appId,
                'client_secret' => $appSecret,
                'grant_type' => 'authorization_code',
            ];

            $response = $this->client->post('/oauth/access_token/', [
                'headers' => [
                    'content-type' => 'application/x-www-form-urlencoded',
                ],
                'form_params' => $body,
            ]);

            $res = $response->getBody();
            $response = json_decode($res, true);
            if($response['message']  == 'error') {
                $this->error('抖音接口异常, '.$response['data']['description'], $response);
            }

            //判断是否是老用户
            $openId = $response['data']['open_id'];

        }

        $usrObj = \app\common\model\User::where('open_id', $openId)->find();

        //新用户注册
        if (empty($usrObj)) {

            $data = [
                'username' => '',
                'nickname' => '',
                'open_id' => $openId,
                'content' => $content,
                'channel_id' => $param['channel_id'] ?? 0,
            ];
            $usrObj = \app\common\model\User::create($data);

        }

        $token = \app\common\model\User::getToken($openId, $usrObj->id);
//        $user = \app\common\model\User::verifyToken($this->request->header(config('jwt.field'), ''));
//        var_dump($user);


        \app\common\model\User::where('open_id', $openId)->update(['token' => $token]);

        //然后直接返回token
        $this->success('登录成功', ['token' => $token, 'open_id' => $openId]);

    }

    //用户数据
    public function info()
    {
        $uid = $this->request->uid;
        $this->success('', ['info' => \app\common\model\User::find($uid)]);

    }

    public function selectResponse($scoreSum, $questionnaireId, $groupIndex)
    {

        if ($scoreSum == 0) {
            $this->error("提交数据异常");
        }

        $where = [
            ['start', '<=', $scoreSum],
            ['lt', '>=', $scoreSum],
            ['questionnaire_id', '=', $questionnaireId],
            ['group_index', '=', $groupIndex]
        ];
        $questionResponse = QuestionResponse::where($where)->find();
        if (empty($questionResponse)) {
            $this->error("联系管理员,得分({$scoreSum})生成问卷({$questionnaireId}-{$groupIndex})报告异常");
        }
        return $questionResponse;
    }

    //批量创建报告
    public function createRes()
    {
        $uid = $this->getUid();
        $param = $this->request->param();

        if (empty($param['options']) || empty($param['questionnaire_id'])) {
            $this->error("参数缺少");
        }

        $levelArr = [];
        //查看报告
        $questionnairesObj = \app\common\model\Questionnaires::find($param['questionnaire_id']);
        if (!empty($questionnairesObj->group_conf)) {
            $levelArr[] = []; //占位符
            foreach ($questionnairesObj->group_conf as $groupConf) {
                $levelArr[] = array_slice($param['options'], $groupConf['start'] - 1, $groupConf['end']);
            }

        } else {
            $levelArr[] = $param['options'];
        }

        //生成报告
        foreach ($levelArr as $group_index => $level) {
            if (empty($level)) {
                continue;
            }

            $scoreSum = array_sum(array_column($level, 'score'));
            //选择报告
            $questionResponse = $this->selectResponse($scoreSum, $param['questionnaire_id'], $group_index);

            //生成用户报告
            //uid questionnaire_id 分数对应报告
            $questionAnswer = $this->saveAnswer($uid, $param['questionnaire_id'], $scoreSum, $questionResponse->id, $level, $group_index);
            QuestionAnswer::where([
                'response_id' => 0,
                'uid' => $uid,
                'questionnaire_id' => $questionnairesObj->id])->delete();

        }

        $this->success("生成报告成功");

    }


    public function saveAnswer($uid, $questionnaireId, $scoreSum, $questionResponseId, $option, $groupIndex)
    {

        //去重判断
        $where = [
            'uid' => $uid,
            'questionnaire_id' => $questionnaireId,
            'group_index' => $groupIndex,
        ];
        $questionAnswer = QuestionAnswer::where($where)->find();
        //判断1、存在未完成报告 2、已完成报告 3、用户没有该问卷报告
        if (!empty($questionAnswer)) {

            //2、已完成报告
            if ($questionAnswer->response_id == $questionResponseId) {
                return 0;
            }

            //1、存在未完成报告
            $questionAnswer->score = $scoreSum;
            $questionAnswer->response_id = $questionResponseId;
            $questionAnswer->updated_at = time();
            $questionAnswer->save();

        } else { //3 用户没有该问卷报告

            $questionAnswer = [
                'json' => json_encode($option ?? ''),
                'created_at' => time(),
                'uid' => $this->getUid(),
                'questionnaire_id' => $questionnaireId,
                'score' => $scoreSum,
                'response_id' => $questionResponseId,
                'group_index' => $groupIndex ?? 0,
            ];

            $questionAnswer = QuestionAnswer::create($questionAnswer);

            if ($questionAnswer->isEmpty()) {
                $this->error("问卷({$questionnaireId})阶段({$groupIndex})生成报告失败");
            }

        }

        return $questionAnswer;

    }


    //创建报告
    public function createOldRes()
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
            ['start', ' <= ', $scoreSum],
            ['lt', ' >= ', $scoreSum],
            ['questionnaire_id', ' = ', $param['questionnaire_id']],
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
                $where[] = ['response_id', '=', 0];
            } else if ($param['is_ok'] == 1) {
                $where[] = ['response_id', '>', 0];
            }
        }

        $questionAnswer = QuestionAnswer::where($where)->group("questionnaire_id")->with(['questionnaire' => function ($query) {
            $query->with(['articleCategorys']);
        }])->select();
        $this->success("", ['list' => $questionAnswer]);
    }

    public function report()
    {
        $param = $this->request->param();

        if (empty($param['questionnaire_id'])) {
            $this->error("参数异常!");
        }
        $ids = QuestionAnswer::where(['questionnaire_id' => $param['questionnaire_id'], 'uid' => $this->getUid()])->column('response_id');
        if (empty($ids)) {
            $this->error("未生成报告");
        }
        $list = QuestionResponse::whereIn('id', $ids)->select();
        $this->success("", ['info' => $list]);

    }


}
