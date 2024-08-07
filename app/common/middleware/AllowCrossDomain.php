<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2021 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace app\common\middleware;

use Closure;
use app\Request;
use think\Response;

/**
 * 跨域请求支持
 */
class AllowCrossDomain
{
    protected array $header = [
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Max-Age'           => 1800,
        'Access-Control-Allow-Methods'     => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers'     => 'Origin,X-Requested-With,Content-Type,x-token,Authorization',
        'Access-Control-Expose-Headers'    => 'Authorization,Content-Disposition'
    ];

    /**
     * 允许跨域请求
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param array|null $header
     * @return Response
     */
    public function handle(Request $request, Closure $next, ? array $header = []): Response
    {
        $header = !empty($header) ? array_merge($this->header, $header) : $this->header;
        if (!isset($header['Access-Control-Allow-Origin'])) {
            $origin = $request->header('origin');

            if ($origin) {
                $header['Access-Control-Allow-Origin'] = $origin;
            } else {
                $header['Access-Control-Allow-Origin'] = '*';
            }
        }

        $r = $next($request);
        // 刷新登录token
        $r->header($header);
        return $r;
    }
}
