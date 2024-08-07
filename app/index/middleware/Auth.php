<?php
declare (strict_types = 1);

namespace app\index\middleware;

use app\common\model\User;
use app\common\traits\Jump;
use app\Request;
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
        $user = User::verifyToken($request->header(config('jwt.field'), ''));
        if ($user) {
            $request->user_id = $user['user_id'];
            $request->nickname = $user['nickname'];
        }
        return $next($request);
    }
}
