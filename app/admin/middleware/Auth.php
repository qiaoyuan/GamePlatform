<?php
declare (strict_types = 1);

namespace app\admin\middleware;

use app\common\model\Admin;
use app\common\model\AdminPermission;
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
        if (!Str::contains($request->pathinfo(), 'account/')) {
            $user = Admin::verifyToken($request->header(config('jwt.field'), ''));
            if (!$user) {
                $this->error('对不起，您还未登录或登录已过期', [], 2);
            }
            $request->admin_id = $user['admin_id'];
            $request->nickname = $user['nickname'];

            $api = trim($request->baseUrl(), '/');
            $check = AdminPermission::checkPermission($request->admin_id, $api);
            if ($check && input('export')) {
                $check = AdminPermission::checkPermission($request->admin_id, $api . '-export');
            }
            if (!$check){
                $this->error('对不起，您没有权限');
            }
        }
        $response = $next($request);
        if ($request->admin_id) {
            $response->header([config('jwt.field') => Admin::getToken($request->admin_id, $request->nickname)]);
        }
        return $response;
    }
}
