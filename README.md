# webman-api-sign
适用于webman的api签名

# 安装
composer require wen-gg/webman-api-sign

# 配置
```php
return [
    'enable' => true,

    //
    'driver' => \Wengg\WebmanApiSign\Driver\ArrayDriver::class, //如有需要可自行实现BaseDriver
    'encrypt' => 'sha256', //加密方式
    'timeout' => 60, //timestamp超时时间秒，0不限制
    'table' => 'app_sign', //表名

    //字段对照，可从(header,get,post)获取的值
    'fields' => [
        'app_key' => 'appKey', //app_key
        'timestamp' => 'timestamp', //时间戳
        'noncestr' => 'noncestr', //随机字符串
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
        ],
    ],
];
```

# 签名计算
注意：签名数据除业务参数外需加上app_key，timestamp，noncestr对应的字段数据
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
    "appKey": "1661408635",
    "timestamp": "1662721474",
    "noncestr": "ewsqam"
}
```
排序后
```json
{
    "a": "1",
    "appKey": "1661408635",
    "b": [
        "你好世界",
        "abc123"
    ],
    "c": {
        "d": "hello",
        "e": []
    },
    "noncestr": "ewsqam",
    "timestamp": "1662721474"
}
```
链接后
```
a=1&appKey=1661408635&b[0]=你好世界&b[1]=abc123&c[d]=hello&noncestr=ewsqam&timestamp=1662721474D81668E7B3F24F4DAB32E5B88EAE27AC
```
加密
```php
$signature = hash('sha256', 'a=1&appKey=1661408635&b[0]=你好世界&b[1]=abc123&c[d]=hello&noncestr=ewsqam&timestamp=1662721474D81668E7B3F24F4DAB32E5B88EAE27AC');
```