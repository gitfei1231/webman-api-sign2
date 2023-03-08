# webman-api-sign
适用于webman的api签名，本插件基于 https://github.com/wen-gg/webman-api-sign 修改，不需要防止重放请求和RSA加密的直接使用原作者的插件即可。

# 安装
composer require gitfei1231/webman-api-sign

# 配置
```php
return [
    'enable' => true,
    
    /**
     * 配置 driver
     * 数组配置驱动   \Wengg\WebmanApiSign\Driver\ArrayDriver::class
     * 数据库配置驱动 \Wengg\WebmanApiSign\Driver\DatabaseDriver::class (使用的是 ThinkORM)
     * 如需要自定义驱动，继承 \Wengg\WebmanApiSign\Driver\BaseDriver::class
    */
    'driver' => \Wengg\WebmanApiSign\Driver\ArrayDriver::class,
    'encrypt' => 'sha256', //加密sign方式
    'timeout' => 60, //timestamp超时时间秒，0不限制
    'table' => 'app_sign', //表名

    /**
     * 防重放请求是否开启 true只能请求一次，时间是上面 timeout内
     * replay 主要借助与 timeout + noncestr随机值进行验证, 一定的时间内noncestr如果重复，那就判定重放请求
     * noncestr 建议生成随机唯一UUID 或者你使用 13位时间戳+18位随机数。1678159075243(13位)+随机数(18位)
     */
    'replay' => false,
    
    /**
     * 如果使用 DatabaseDriver 需要缓存查询后的数据
     * 设置缓存时间即可缓存对应的app_id数据
     * db_cache_time => null 关闭缓存
     */
    'db_cache_time' => 604800, // null 关闭缓存

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
            'app_secret' => 'D81668E7B3F24F4DAB32E5B88EAE27AC', //应用秘钥
            'app_name' => '默认', //应用名称
            'status' => 1, //状态：0=禁用，1=启用
            'expired_at' => null, //过期时间，例如：2023-01-01 00:00:00，null不限制
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
```

# 指定路由不需要签名验证
#### 不设置 setParams 或者 设置notSign为 false 都要经过验证
```php
// 此路由不经过sign验证
Route::get('/login', [app\api\controller\LoginController::class, 'login'])->setParams(['notSign' => true]);

// 此路由经过sign验证
Route::get('/login', [app\api\controller\LoginController::class, 'login'])->setParams(['notSign' => false]);

// 此路由经过sign验证
Route::get('/login', [app\api\controller\LoginController::class, 'login']);
```

# 开启非对称加密 rsa_status
注意：开启后客户端需自行随机动态生成app_secret（不开启则使用服务端固定的app_secret），用公钥进行加密app_secret，服务器端会进行解密出app_secret, 生成sign进行比对，非对称加密算法为 aes-128-cbc
1. app_secret 客户端自行生成
2. sign使用自动生成的app_secret按照下面签名算法客户端计算出来
3. 使用公钥加密app_secret，通过header中的appKey字段进行传输（未开启rsa，此字段不用传）

### RS256 生成 公钥和私钥
```php
ssh-keygen -t rsa -b 4096 -E SHA256 -m PEM -P "" -f RS256.key
openssl rsa -in RS256.key -pubout -outform PEM -out RS256.key.pub
```

# 开启body报文加密 encrypt_body，非明文传输参数安全性更高（不加密get参数）
注意：如果启用的RSA，那么需使用自行随机动态生成app_secret进行对称加密（否则使用服务端固定的app_secret进行对称加密）

接口使用https已经可以达到报文加密的作用了，开发这个为啥？因为防止 “中间人”抓包，使用代理软件抓包可以获取https明文数据

##### 1、开启了rsa_status
1. 把body传输的json数据进行转为字符串
2. 使用自动生成的app_secret作为密钥进行aes-128-cbc对称加密
3. 将加密后的字符串直接通过body进行传输

##### 2、未开启rsa_status
1. 把body传输的json数据进行转为字符串
2. 使用固定的app_secret作为密钥进行aes-128-cbc对称加密
3. 将加密后的字符串直接通过body进行传输


# php对称加密代码例子
##### Tips：前端客户端很多加密解密参考php端自行实现加密和解密
```php
class AES
{
    private $key;
    private $method = 'aes-128-cbc';
    
    public function __construct($key)
    {
        $this->key = $key;
    }
    
    /**
     * 加密
     * @author tangfei <957987132@qq.com> 2023-03-07
     * @param string $plaintext 加密内容
     * @return string
     */
    public function encrypt(string $plaintext)
    {
        // 获取加密算法要求的初始化向量的长度
        $ivlen = openssl_cipher_iv_length($this->method);
        // 生成对应长度的初始化向量. aes-128模式下iv长度是16个字节, 也可以自由指定.
        $iv = openssl_random_pseudo_bytes($ivlen);
        // 加密数据
        $ciphertext = openssl_encrypt($plaintext, $this->method, $this->key, 1, $iv);
        
        return base64_encode($iv . $ciphertext);
    }

    /**
     * 解密
     * @author tangfei <957987132@qq.com> 2023-03-07
     * @param string $ciphertext 加密内容
     * @return string
     */
    public function decrypt($ciphertext)
    {
        $ciphertext = base64_decode($ciphertext);
        $ivlen = openssl_cipher_iv_length($this->method);
        $iv = substr($ciphertext, 0, $ivlen);
        $ciphertext = substr($ciphertext, $ivlen);
        
        $plaintext = openssl_decrypt($ciphertext, $this->method, $this->key, 1, $iv);
        return $plaintext;
    }
}
```

# 再support/Request.php新增方法
```php
class Request extends \Webman\Http\Request
{
    /**
     * 设置post参数
     * @author tangfei <957987132@qq.com> 2023-03-07
     * @param array $data
     * @return void
     */
    public function setPostData(array $data)
    {
        if(empty($data)){ return; }
        $this->post();
                
        foreach ($data as $key => $value) {
            $this->_data['post'][$key] = $value;
        }
    }
}
```

# 签名计算
注意：签名数据除业务参数外需加上app_key，timestamp，nonceStr对应的字段数据，上面的加密报文和签名sign计算不相干，sign还是按照传输的字段进行计算，密文到后端会转为字段后再进行sign计算
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
// $data = sortData($data);
// $str = urldecode(http_build_query($data)) . $key;
// $signature = hash('sha256', $str);
$signature = hash('sha256', 'a=1&appId=1661408635&b[0]=你好世界&b[1]=abc123&c[d]=hello&nonceStr=ewsqam&timestamp=1662721474D81668E7B3F24F4DAB32E5B88EAE27AC');
```