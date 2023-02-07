<?php

return [
    'enable' => true,

    //配置
    'driver' => \Wengg\WebmanApiSign\Driver\ArrayDriver::class, //如有需要可自行实现BaseDriver DatabaseDriver ArrayDriver
    'encrypt' => 'sha256', //加密方式
    'timeout' => 60, //timestamp超时时间秒，0不限制
    'table' => 'app_sign', //表名
    //如果使用 DatabaseDriver 需要缓存查询后的数据
    'cache' => [
        'key' => 'app_sign_app_key', // false 关闭缓存
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
        ],
    ],
];
