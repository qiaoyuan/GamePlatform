<?php
namespace app\common\traits;

use think\exception\HttpResponseException;
use think\Response;

trait Jump {

    private bool $setJson = false;

    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param  string     $msg 提示信息
     * @param mixed|string $data 返回的数据
     * @return void
     */
    protected function success(string $msg = '', $data = '')
    {
        $this->result($data,0, $msg);
    }

    /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param string $msg 提示信息
     * @param mixed|string $data 返回的数据
     * @param int $code
     * @return void
     */
    protected function error(string $msg = '', $data = '', int $code = 1)
    {
        $this->result($data, $code, $msg);
    }

    /**
     * 返回封装后的API数据到客户端
     * @access protected
     * @param  mixed     $data 要返回的数据
     * @param  integer   $code 返回的code
     * @param  mixed     $msg 提示信息
     * @param  string    $type 返回数据格式
     * @param  array     $header 发送的Header信息
     * @return void
     */
    protected function result($data, int $code = 0, string $msg = '', string $type = '', array $header = [])
    {
        $result = [
            'code' => $code,
            'message'  => $msg,
            'data' => $data,
        ];

        $type     = $type ?: $this->getResponseType();
        if ($type === 'json') {
            $response = Response::create($result, $type)->header($header);
            $response->options([
                'json_encode_param' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            ]);
        } else {
            $response = Response::create('404', $type)->header($header);
        }

        throw new HttpResponseException($response);
    }

    /**
     * URL重定向
     * @access protected
     * @param  string         $url 跳转的URL表达式
     * @param integer $code http code
     * @return void
     */
    protected function redirect(string $url, int $code = 302)
    {
        throw new HttpResponseException(redirect($url, $code));
    }

    /**
     * 获取当前的response 输出类型
     * @access protected
     * @return string
     */
    protected function getResponseType(): string
    {
        return $this->setJson
            ? 'json'
            : ((request()->isAjax() || in_array(request()->subDomain(), [config('domain.api')])) ? 'json' : 'html');
    }

    protected function systemError()
    {
        $this->error('系统忙，请稍后再试，或联系在线客服处理');
    }

    protected function isJson(): bool
    {
        return $this->getResponseType() === 'json';
    }

    protected function display404(): Response
    {
        if ($this->isJson()) {
            return json([
                'code' => 1,
                'message' => '对不起，您访问的页面不存在',
                'data' => ''
            ], 404);
        }
        return Response::create('404');
    }

    protected function displayError($msg, $url): Response
    {
        if ($this->isJson()) {
            return json([
                'code' => 1,
                'message' => $msg,
                'data' => ''
            ]);
        }
        return Response::create($msg);
    }

    protected function setJson($is = true)
    {
        $this->setJson = $is;
    }
}
