# webman-api-sign
适用于webman的api签名

# 安装
composer require gitfei1231/webman-api-sign

# 配置
```php
return [
    'enable' => true,
    
    //配置
    'driver' => \Wengg\WebmanApiSign\Driver\ArrayDriver::class, //如有需要可自行实现BaseDriver
    'encrypt' => 'sha256', //加密方式
    'timeout' => 60, //timestamp超时时间秒，0不限制
    'table' => 'app_sign', //表名
    //如果使用 DatabaseDriver 需要缓存查询后的数据
    'cache' => [
        'key' => 'app_sign_app_key',
        'timeout' => 604800
    ], 
    'replay' => false, //防重放请求是否开启 true只能请求一次

    //字段对照，可从(header,get,post)获取的值
    'fields' => [
        'app_key' => 'appId', //app_key
        'timestamp' => 'timestamp', //时间戳
        'noncestr' => 'nonceStr', //随机字符串
        'signature' => 'signature', //签名字符串
    ],

    //driver为ArrayDriver时生效，对应table
    'app_sign' => [
        [
            'app_key' => '1661408635', //应用key
            'app_secret' => 'D81668E7B3F24F4DAB32E5B88EAE27AC', //应用秘钥
            'app_name' => '默认', //应用名称
            'status' => 1, //状态：0=禁用，1=启用
            'expired_at' => null, //过期时间，例如：2023-01-01 00:00:00，null不限制
            'rsa_status' => 0, //状态：0=禁用，1=启用
            /**
             * sign私钥 RS256加密
             */
            'private_key' => <<<EOD
-----BEGIN RSA PRIVATE KEY-----
...
-----END RSA PRIVATE KEY-----
EOD,
            /**
             * sign公钥 RS256加密
             */
            'public_key' => <<<EOD
-----BEGIN PUBLIC KEY-----
...
-----END PUBLIC KEY-----
EOD
        ],
    ],
];
```

# 如果开启了rsa_status
那么客户端随机生成app_secret，用公钥进行加密app_secret，服务器端会进行解密出app_secret进行比对

# 不需要签名验证 notSign
#### 不设置 setParams 和 设置notSign为 false 都要经过验证
```php
Route::get('/login', [app\api\controller\LoginController::class, 'login'])->setParams(['notSign' => true]);
```

# 签名计算
注意：签名数据除业务参数外需加上app_key，timestamp，nonceStr对应的字段数据
1. 签名数据先按照键名升序排序
2. 使用 & 链接签名数组（参数不转义，空数据不参与加密），再在尾部加上app_secret
3. 再根据配置的加密方式 hash() 签名数据

# 示例

排序前
```json
{
    "a": "1",
    "b": [
        "你好世界",
        "abc123"
    ],
    "c": {
        "e": [],
        "d": "hello"
    },
    "appId": "1661408635",
    "timestamp": "1662721474",
    "nonceStr": "ewsqam"
}
```
排序后
```json
{
    "a": "1",
    "appId": "1661408635",
    "b": [
        "你好世界",
        "abc123"
    ],
    "c": {
        "d": "hello",
        "e": []
    },
    "nonceStr": "ewsqam",
    "timestamp": "1662721474"
}
```
链接后
```
a=1&appId=1661408635&b[0]=你好世界&b[1]=abc123&c[d]=hello&nonceStr=ewsqam&timestamp=1662721474D81668E7B3F24F4DAB32E5B88EAE27AC
```
加密
```php
$signature = hash('sha256', 'a=1&appId=1661408635&b[0]=你好世界&b[1]=abc123&c[d]=hello&nonceStr=ewsqam&timestamp=1662721474D81668E7B3F24F4DAB32E5B88EAE27AC');
```