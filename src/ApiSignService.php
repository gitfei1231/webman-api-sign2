<?php

namespace Wengg\WebmanApiSign;
use support\think\Cache;

class ApiSignService
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Wengg\WebmanApiSign\Driver\BaseDriver
     */
    protected $driver;

    public function __construct()
    {
        $this->config = config('plugin.gitfei1231.webman-api-sign.app') ?: [];
        if ($this->config) {
            $this->driver = new $this->config['driver']($this->config);
        }
    }

    /**
     * 获取配置
     * @author mosquito <zwj1206_hi@163.com> 2022-08-25
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * 获取驱动
     * @author mosquito <zwj1206_hi@163.com> 2022-08-25
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * 数据排序
     * @param array $data
     * @author mosquito <zwj1206_hi@163.com> 2022-08-25
     */
    public function sortData(array $data)
    {
        $sort = function (array &$data) use (&$sort) {
            ksort($data);
            foreach ($data as &$value) {
                if (is_array($value)) {
                    $sort($value);
                }
            }
        };
        $sort($data);
        return $data;
    }

    /**
     * 签名
     * @author mosquito <zwj1206_hi@163.com> 2022-08-25
     */
    public function sign(array $data, string $key)
    {
        unset($data[$this->config['fields']['app_key']]);
        unset($data[$this->config['fields']['signature']]);
        if (!isset($data[$this->config['fields']['app_id']]) || !isset($data[$this->config['fields']['timestamp']]) || !isset($data[$this->config['fields']['noncestr']])) {
            throw new ApiSignException("签名错误", ApiSignException::PARAMS_ERROR);
        }

        //应用数据
        $app_sign = $this->driver->getInfo($data[$this->config['fields']['app_id']]);
        if (!$app_sign) {
            throw new ApiSignException("应用id未找到", ApiSignException::APPKEY_NOT_FOUND);
        }
        if ($app_sign['status'] != 1) {
            throw new ApiSignException("应用id已禁用", ApiSignException::APPKEY_DISABLE);
        }
        if ($app_sign['expired_at'] && $app_sign['expired_at'] < date('Y-m-d H:i:s')) {
            throw new ApiSignException("应用id已过期", ApiSignException::APPKEY_EXPIRED);
        }

        $data = $this->sortData($data);
        $encrypt = $this->config['encrypt'] ?: 'sha256';
        $str = urldecode(http_build_query($data)) . $key;
        $signature = hash($encrypt, $str);
        
        return $signature;
    }

    /**
     * 验签
     * @author mosquito <zwj1206_hi@163.com> 2022-08-25
     */
    public function check(array $data, string $key)
    {
        if (!$signature = $data[$this->config['fields']['signature']]) {
            throw new ApiSignException("签名错误", ApiSignException::PARAMS_ERROR);
        }
        if ($signature !== $this->sign($data, $key)) {
            throw new ApiSignException("签名验证失败", ApiSignException::SIGN_VERIFY_FAIL);
        }

        $ts = time();
        // 优化时间 上下 timeout 内都正常
        if ($this->config['timeout'] && $this->config['timeout'] > 0 && ($data[$this->config['fields']['timestamp']] + $this->config['timeout'] < $ts || $data[$this->config['fields']['timestamp']] - $this->config['timeout'] > $ts)) {
            throw new ApiSignException("签名超时", ApiSignException::SIGN_TIMEOUT);
        }
        
        if($this->config['replay']){
            $noncestr = $data[$this->config['fields']['noncestr']];
            $ip = \request()->getRealIp() ?? '';
            $key = 'api_replay_' . $ip . '_' . $noncestr;
            
            if (Cache::has($key)) {
                throw new ApiSignException("请求失效", ApiSignException::REQUEST_INVALID);
            }else{
                //存储 noncestr
                Cache::set($key, true, $this->config['replay_timeout']); // 设置过期时间，这里假设 replay_timeout 是一个合适的过期时间
            }
        }
    }
}
