<?php
declare (strict_types = 1);

namespace app\index\middleware;

use app\common\model\User;
use app\common\traits\Jump;
use app\Request;
use think\helper\Str;
use think\Response;

class Auth
{
    use Jump;
    /**
     * 处理请求
     *
     * @param Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle(Request $request, \Closure $next) :Response
    {
        if (!Str::contains($request->pathinfo(), ['user/login', 'home/', 'order/callback'])) {

            $user = User::verifyToken($request->header(config('jwt.field'), ''));

            if ($user) {
                $request->uid = $user->uid;
                $request->open_id = $user->open_id;
            } else {
                $this->error('登录失效', '', 401);
                $request->uid = 999;
            }

        }

        return $next($request);
    }
}
