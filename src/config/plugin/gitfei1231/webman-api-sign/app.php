<?php

return [
    'enable' => true,

    /**
     * 配置 driver
     * 数组配置驱动   \Wengg\WebmanApiSign\Driver\ArrayDriver::class
     * 数据库配置驱动 \Wengg\WebmanApiSign\Driver\DatabaseDriver::class (使用的是 ThinkORM)
     * 如需要自定义驱动，继承 \Wengg\WebmanApiSign\Driver\BaseDriver::class
    */
    'driver' => \Wengg\WebmanApiSign\Driver\ArrayDriver::class,
    'encrypt' => 'sha256', //加密sign方式,
    'timeout' => 3600,  //接口sign超时范围，客户端请求的timestamp和服务器时间对比出超时时间秒，0不限制
    'table' => 'app_sign', //表名
    
    /**
     * 防重放请求是否开启 true只能在一定时间内请求一次
     * replay 主要借助 ip + noncestr 随机值进行验证, 一定的时间内noncestr如果重复，那就判定重放请求
     * noncestr 建议生成随机唯一UUID 或者你使用 13位时间戳+18位随机数。1678159075243(13位)+随机数(18位)
     */
    'replay' => false, 
    'replay_timeout' => 604800,  //接口重放超时时间秒，客户端在本时间范围内相同的ip + noncestr不可重复请求，过时后清空，0永久缓存永远不能二次重放请求
    
    /**
     * 如果使用 DatabaseDriver 需要缓存查询后的数据
     * 设置缓存时间即可缓存对应的app_id数据
     * db_cache_time => null 关闭缓存
     */
    'db_cache_time' => null, // null 关闭缓存

    //字段对照，可从(header,get,post)获取的值
    'fields' => [
        'app_id'     => 'appId',     //app_id
        'app_key'    => 'appKey',    //app_key rsa加密才需要传，appKey为前端随机生成的app_secret秘钥，用于加密sign和报文数据
        'timestamp'  => 'timestamp', //时间戳
        'noncestr'   => 'nonceStr',  //随机字符串
        'signature'  => 'signature', //签名字符串
    ],

    //driver为ArrayDriver时生效，对应table
    'app_sign' => [
        [
            'app_id' => '1661408635', //应用id
            'app_name' => '默认', //应用名称
            'status' => 1,        //状态：0=禁用，1=启用
            'expired_at' => null, //过期时间，例如：2023-01-01 00:00:00，null不限制
            'app_secret' => 'D81668E7B3F24F4DAB32E5B88EAE27AC', //应用秘钥 不启用RSA使用
            'encrypt_body' => 0, //状态：0=禁用，1=启用 算法：aes-128-cbc 是否加密body传入加密后的报文字符串，启用RSA需要使用自动生成的app_secret进行对称加密，否则使用固定的app_secret进行对称加密
            'rsa_status' => 0, //状态：0=禁用，1=启用 启用RSA，主要用rsa加密随机生成的app_secret，而不使用固定app_secret
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
