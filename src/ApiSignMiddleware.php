<?php

namespace Wengg\WebmanApiSign;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class ApiSignMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        // 默认路由 $request->route 为null，所以需要判断 $request->route 是否为空
        $route = $request->route;
        
        if ($route && !$route->param('notSign')) {
            $service = new ApiSignService;
            $config = $service->getConfig();
            if (!$config) {
                return $next($request);
            }
            $fields = $config['fields'];
            $data = array_merge($request->all(), [
                $fields['app_key'] => $request->header($fields['app_key'], $request->input($fields['app_key'])),
                $fields['timestamp'] => $request->header($fields['timestamp'], $request->input($fields['timestamp'])),
                $fields['noncestr'] => $request->header($fields['noncestr'], $request->input($fields['noncestr'])),
                $fields['signature'] => $request->header($fields['signature'], $request->input($fields['signature'])),
            ]);
            $service->check($data, $request->input($fields['signature']));
        }

        return $next($request);
    }
}
