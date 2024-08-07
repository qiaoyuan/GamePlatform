<?php
namespace app;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use think\response\Json;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    protected $header = [
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Max-Age'           => 1800,
        'Access-Control-Allow-Methods'     => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers'     => 'Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With, x-token',
        'Access-Control-Expose-Headers'     => 'Authorization, Content-Type, X-CSRF-TOKEN, X-Requested-With, x-token',
    ];

    public $request;
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        $this->request = $request;
        if (!$e instanceof HttpResponseException) {
            trace($e->__toString(), 'error');
        }
        if ($e instanceof ModelNotFoundException || $e instanceof DataNotFoundException) {
            if ($this->getResponseType() === 'html') {
                $r = Response::create('404');
            } else {
                $r = json([
                    'code' => 1,
                    'message' => '数据不存在',
                    'data' => ''
                ]);
            }
        } else if (!$e instanceof HttpResponseException && !env('app_status')) {
            if ($this->getResponseType() === 'html') {
                $r = Response::create('404');
            } else {
                $r = json([
                    'code' => 1,
                    'message' => '系统错误，请稍后再试',
                    'data' => ''
                ]);
            }
        } else {
            $r = parent::render($request, $e);
        }
        $this->header['Access-Control-Allow-Origin'] = $request->header('origin') ?: '*';
        $r->header($this->header);
        return $r;
    }

    protected function convertExceptionToResponse(Throwable $exception): Response
    {
        $response = parent::convertExceptionToResponse($exception);
        if ($response instanceof Json) {
            if ($exception instanceof ModelNotFoundException || $exception instanceof DataNotFoundException) {
                $data = $response->getData();
                $data['message'] = '数据不存在';
                $response->data($data);
            }
            if (!$exception instanceof HttpResponseException) {
                $data = $response->getData();
                $data['code'] = 1;
                $response->data($data);
            }
        }
        return $response->code($response->getCode() == 500 ? 200 : $response->getCode());
    }

    /**
     * 获取错误编码
     * ErrorException则使用错误级别作为错误编码
     * @access protected
     * @param Throwable $exception
     * @return integer                错误编码
     */
    protected function getCode(Throwable $exception)
    {
        $code = parent::getCode($exception);
        if ($exception instanceof ModelNotFoundException || $exception instanceof DataNotFoundException) {
            $code = 1;
        }
        return $code;
    }

    public function getResponseType(): string
    {
        return (request()->isAjax() || in_array(request()->subDomain(), [
                config('domain.api')
            ]))
            ? 'json' : 'html';
    }
}
