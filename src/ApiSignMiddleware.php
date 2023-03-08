<?php

namespace Wengg\WebmanApiSign;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use Wengg\WebmanApiSign\common\Util;

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
            $data = [
                $fields['app_key'] => $request->header($fields['app_key'], $request->input($fields['app_key'])),
                $fields['timestamp'] => $request->header($fields['timestamp'], $request->input($fields['timestamp'])),
                $fields['noncestr'] => $request->header($fields['noncestr'], $request->input($fields['noncestr'])),
                $fields['signature'] => $request->header($fields['signature'], $request->input($fields['signature'])),
            ];

            $app_info = $service->getDriver()->getInfo($data[$fields['app_key']]);
            if(empty($app_info)){
                throw new ApiSignException("签名参数错误", ApiSignException::PARAMS_ERROR);
            }

            //判断是否启用rsa算法，解密post数据
            if($app_info['rsa_status'] && $request->method() === 'POST'){
                try{
                    $arr  = Util::rsa_decode($data[$fields['signature']], $app_info['private_key']);
                    $arr  = \json_decode($arr,true);
                    $key  = $arr['app_secret'] ?? '';
                    $request->setHeaderData([
                        $fields['signature'] => $arr['sign'] ?? ''
                    ]);
                    
                    //解密数据
                    $postData = Util::decrypt($request->rawBody(), $key);
                    $request->setPostData($postData);

                } catch ( \Exception $e ) {
                    throw new ApiSignException("签名参数错误", ApiSignException::PARAMS_ERROR);
                }
            }else{
                $key = $app_info['app_secret'];
            }

            $data = array_merge($request->all(), [
                $fields['app_key'] => $request->header($fields['app_key'], $request->input($fields['app_key'])),
                $fields['timestamp'] => $request->header($fields['timestamp'], $request->input($fields['timestamp'])),
                $fields['noncestr'] => $request->header($fields['noncestr'], $request->input($fields['noncestr'])),
                $fields['signature'] => $request->header($fields['signature'], $request->input($fields['signature'])),
            ]);
            
            $service->check($data, $key);
        }

        return $next($request);
    }
}
