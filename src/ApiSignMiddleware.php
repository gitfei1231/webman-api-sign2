<?php

namespace Wengg\WebmanApiSign;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class ApiSignMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        //
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
        $service->check($data);

        return $next($request);
    }
}
