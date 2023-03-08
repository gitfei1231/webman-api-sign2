<?php

namespace Wengg\WebmanApiSign;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;
use Wengg\WebmanApiSign\Encryption\RSA;
use Wengg\WebmanApiSign\Encryption\AES;

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
                $fields['app_id'] => $request->header($fields['app_id'], $request->input($fields['app_id'])),
                $fields['app_key'] => $request->header($fields['app_key'], $request->input($fields['app_key'])),
                $fields['timestamp'] => $request->header($fields['timestamp'], $request->input($fields['timestamp'])),
                $fields['noncestr'] => $request->header($fields['noncestr'], $request->input($fields['noncestr'])),
                $fields['signature'] => $request->header($fields['signature'], $request->input($fields['signature'])),
            ];
            
            $app_info = $service->getDriver()->getInfo($data[$fields['app_id']]);
            if(empty($app_info)){
                throw new ApiSignException("签名参数错误", ApiSignException::PARAMS_ERROR);
            }

            //判断是否启用rsa算法，解密body数据
            if($app_info['rsa_status'] && !empty($data[$fields['app_key']]) && !empty($request->rawBody())){
                try{
                    $key  = RSA::rsa_decode($data[$fields['app_key']], $app_info['private_key']);
                    //解密数据
                    $rawData = $request->rawBody();
                    if(!empty($key)){
                        $aes = new AES($key);
                        $postData = $aes->decrypt($rawData);
                        $request->setPostData($postData);
                    }
                    
                } catch ( \Exception $e ) {
                    throw new ApiSignException("签名参数错误：".$e->getMessage(), ApiSignException::PARAMS_ERROR);
                }
            }else{
                $key = $app_info['app_secret'];
            }
            
            $data = array_merge($request->all(), $data);

            $service->check($data, $key);
        }

        return $next($request);
    }
}
