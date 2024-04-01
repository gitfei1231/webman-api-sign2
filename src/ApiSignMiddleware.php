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

        // 获取控制器信息
        $class = new \ReflectionClass($request->controller);
        $properties = $class->getDefaultProperties();
        $noNeedSign = array_map('strtolower', $properties['noNeedSign'] ?? []);
        $ControlNotSign = !(in_array(strtolower($request->action), $noNeedSign) || in_array('*', $noNeedSign));
        $routeNotSign = $route && $route->param('notSign') !== null ? $route->param('notSign') : false;
        
        if ($ControlNotSign || $routeNotSign) {
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
            
            if(empty($data[$fields['app_id']]) || empty($data[$fields['app_key']]) || empty($data[$fields['timestamp']]) || empty($data[$fields['noncestr']]) || empty($data[$fields['signature']])){
                throw new ApiSignException("参数错误", ApiSignException::PARAMS_ERROR);
            }

            $app_info = $service->getDriver()->getInfo($data[$fields['app_id']]);
            if(empty($app_info)){
                throw new ApiSignException("应用id未找到", ApiSignException::APPKEY_NOT_FOUND);
            }

            //判断是否启用rsa算法
            if($app_info['rsa_status']){
                if(empty($data[$fields['app_key']])){
                    throw new ApiSignException("签名错误", ApiSignException::PARAMS_ERROR);
                }
                try{
                    $key  = RSA::rsa_decode($data[$fields['app_key']], $app_info['private_key']);
                } catch ( \Exception $e ) {
                    throw new ApiSignException(config('app.debug') ? "密文解析错误：".$e->getMessage() : "密文解析错误", ApiSignException::APPKEY_ERROR);
                }
            }else{
                $key = $app_info['app_secret'];
            }
            
            //解密数据
            try{
                $rawData = $request->rawBody();
                if($app_info['encrypt_body'] && !empty($key) && !empty($rawData))
                {
                    $aes = new AES($key);
                    $postData = $aes->decrypt($rawData);
                    $postData = \json_decode($postData, true);
                    if(!is_array($postData)){
                        throw new ApiSignException("加密报文必须为JSON字符串", ApiSignException::JSON_ERROR);
                    }
                    $request->setPostData($postData);
                }
            } catch ( \Exception $e ) {
                throw new ApiSignException("加密报文解析错误", ApiSignException::BODY_ERROR);
            }
            
            $data = array_merge($postData ?? $request->post(), $request->get(), $data);
            $service->check($data, $key);
        }

        return $next($request);
    }
}
