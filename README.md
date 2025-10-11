# webman-api-sign
适用于webman项目的api签名，本插件基于 https://github.com/wen-gg/webman-api-sign 修改，不需要防止重放请求和RSA加密的直接使用原作者的插件即可。

# 更新日志
2024-03-18 修复无法安装问题

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
            'app_id' => '1161408635', //应用id
            'app_name' => '默认', //应用名称
            'status' => 1,        //状态：0=禁用，1=启用
            'expired_at' => null, //过期时间，例如：2023-01-01 00:00:00，null不限制
            'app_secret' => 'D24668E7B3F24F4DAB32E5B88EAE25AC', //应用秘钥 不启用RSA使用
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
```php
// 控制器中配置排除sign校验
class TestController
{
    /**
     * 无需sign校验
     * index、save不需要校验 ['index','save']
     * 所有方法都不需要sign校验 ['*'] 
     * @var string[]
     */
    protected $noNeedSign = ['login'];

    /**
     * 登录
     */
    public function login()
    {

    }
}
```

# 开启非对称加密 rsa_status
注意：开启后客户端需自行随机动态生成app_secret（不开启则使用服务端固定的app_secret），用公钥进行加密app_secret，服务器端会进行解密出app_secret, 生成sign进行比对。

> 非对称加密算法为 RSAES-PKCS1-V1_5

1. app_secret 客户端自行生成
2. sign使用自动生成的app_secret按照下面签名算法客户端计算出来
3. 使用公钥加密app_secret，通过header中的appKey字段进行传输（未开启rsa，此字段不用传）


### php端非对称加密和解密代码例子
```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

//当前库
use Wengg\WebmanApiSign\Encryption\RSA;

///=================================下面是生成公钥和私钥================================///
$rsa = new RSA();

// 生成公钥和私钥
$data = $rsa->rsa_create();

echo "私钥：\n";
echo $data['private_key'];
echo "\n";
echo "\n";
echo "公钥：\n";
echo $data['public_key'];

///=================================下面是解密 加密=================================///

// 私钥，可以使用 上面生成 $data['private_key']
$private_key = '-----BEGIN PRIVATE KEY-----
xxxxxxxxxxxxxx
-----END PRIVATE KEY-----
';

// 公钥，可以使用 上面生成 $data['public_key']
$public_key = '-----BEGIN PUBLIC KEY-----
xxxxxxxxxxxxxx
-----END PUBLIC KEY-----
';

// 加密内容
$str1 = "你好，二蛋！";

$str2 = $rsa->rsa_encode($str1, $public_key);

echo "加密内容：\n";
echo $str2;
echo "\n";
echo "\n";

$res = $rsa->rsa_decode($str2, $private_key);

echo "解密内容：\n";
echo $res;
```

### js端非对称加密和解密代码例子
```js
const rs = require('jsrsasign');
const fs = require('fs');

// 加载私钥和公钥
const private_key = fs.readFileSync('./key/private.pem', 'utf8');
const public_key = fs.readFileSync('./key/public.pem', 'utf8');

// 待加密的数据
const data = 'Hello, world!';

// 解析公钥，将字符串转换为 KeyObject 对象
const publicKeyObj = rs.KEYUTIL.getKey(public_key);

// 使用公钥加密数据
const encryptedData = publicKeyObj.encrypt(data, 'RSAES-PKCS1-V1_5');

// 将加密后的数据转换成 Base64 编码
const base64CipherText = rs.hex2b64(encryptedData);

// 输出加密后的数据
console.log(`加密后的数据：${base64CipherText}`);

// 将 Base64 编码的密文还原成二进制数据
const binaryCiphertext = rs.b64tohex(base64CipherText);

// 解析私钥，将字符串转换为 KeyObject 对象
const privateKeyObj = rs.KEYUTIL.getKey(private_key);

// 使用私钥解密数据
const decryptedData = privateKeyObj.decrypt(binaryCiphertext, 'RSAES-PKCS1-V1_5');

// 输出解密后的数据
console.log(`解密后的数据：${decryptedData}`);
```

# 开启body报文加密 encrypt_body，非明文传输参数安全性更高（不加密get参数）
注意：如果启用的RSA，那么需使用自行随机动态生成app_secret进行对称加密（否则使用服务端固定的app_secret进行对称加密）

> app_secret秘钥必须为32位，如：3ddc81a729c34c50b097a098b0512f16

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
```php
<?php
namespace Wengg\WebmanApiSign\Encryption;

// 当前库就用的此类
class AES
{
    //$key秘钥必须为32位，如：3ddc81a729c34c50b097a098b0512f16
    private $key; 
    private $method = 'aes-128-cbc';
    
    public function __construct($key)
    {
        $this->key = hex2bin($key);
    }
    
    /**
     * 加密
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
        $ciphertext = openssl_encrypt($plaintext, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);
        
        return base64_encode($iv . $ciphertext);
    }

    /**
     * 解密
     * @param string $ciphertext 加密内容
     * @return string
     */
    public function decrypt($ciphertext)
    {
        $ciphertext = base64_decode($ciphertext);
        $ivlen = openssl_cipher_iv_length($this->method);
        $iv = substr($ciphertext, 0, $ivlen);
        $ciphertext = substr($ciphertext, $ivlen);
        
        $plaintext = openssl_decrypt($ciphertext, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);
        return $plaintext;
    }
}
```

# JS端对称加密/解密类，可与本库加解密互通
```js
const CryptoJS = require("crypto-js");

class AES {
  constructor(key) {
    //key秘钥必须为32位，如：3ddc81a729c34c50b097a098b0512f16
    this.key = key;
    this.method = "aes-128-cbc";
  }

  encrypt(plaintext) {
    const iv = CryptoJS.lib.WordArray.random(16);
    const ciphertext = CryptoJS.AES.encrypt(
      plaintext,
      this.key,
      { iv: iv, padding: CryptoJS.pad.Pkcs7, mode: CryptoJS.mode.CBC }
    );
    return iv.concat(ciphertext.ciphertext).toString(CryptoJS.enc.Base64);
  }

  decrypt(ciphertext) {
    ciphertext = CryptoJS.enc.Base64.parse(ciphertext);
    const iv = ciphertext.clone();
    iv.sigBytes = 16;
    iv.clamp();
    ciphertext.words.splice(0, 4); // remove IV from ciphertext
    ciphertext.sigBytes -= 16;
    const decrypted = CryptoJS.AES.decrypt(
      { ciphertext: ciphertext },
      this.key,
      { iv: iv, padding: CryptoJS.pad.Pkcs7, mode: CryptoJS.mode.CBC }
    );
    const plaintext = decrypted.toString(CryptoJS.enc.Utf8);
    return plaintext;
  }

  toHex() {
    return this.key.toString(CryptoJS.enc.Hex);
  }

  static fromHex(hexString) {
    return new AES(CryptoJS.enc.Hex.parse(hexString));
  }

  static fromBase64(base64String) {
    return new AES(CryptoJS.enc.Base64.parse(base64String));
  }

  toBase64() {
    return this.key.toString(CryptoJS.enc.Base64);
  }
}

// 秘钥
const keyHex = '0123456789abcdef0123456789abcdef';
const aes = AES.fromHex(keyHex);

// 加密使用示例
const plaintext = '你好，二蛋！';
const encrypted = aes.encrypt(plaintext);
console.log("加密内容：", encrypted);

// 解密使用示例
const str = aes.decrypt(encrypted);
console.log("解密内容：", str);
```
>其他客户端语言加解密可以参考上面php/js端类进行编写


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

### 方法一：提供一个js http_build_query 比较高效的写法
```js
// 该函数的实现方式和 PHP 中的 http_build_query
function http_build_query(data, prefix = null) {
  const queryParts = [];

  for (const [key, value] of Object.entries(data)) {
    // 处理数组和对象
    if (typeof value === "object" && value !== null) {
      // 判断是否为空数组或空对象
      if (Array.isArray(value) && value.length === 0) {
        continue;
      }
      if (Object.prototype.toString.call(val[k]) === '[object Object]' && Object.keys(value).length === 0) {
        continue;
      }
      const newPrefix = prefix ? `${prefix}[${encodeURIComponent(key)}]` : encodeURIComponent(key);
      queryParts.push(http_build_query(value, newPrefix));
    }
    // 处理 true 值
    else if (value === true) {
      const encodedKey = encodeURIComponent(prefix ? `${prefix}[${encodeURIComponent(key)}]` : encodeURIComponent(key));
      queryParts.push(`${encodedKey}=1`);
    } 
    // 处理 false 值
    else if (value === false) {
      const encodedKey = encodeURIComponent(prefix ? `${prefix}[${encodeURIComponent(key)}]` : encodeURIComponent(key));
      queryParts.push(`${encodedKey}=0`); // 加上等号
    }
    // 处理 null 值
    else if (value === null) {
      // 空值直接跳过
      continue;
      // const encodedKey = encodeURIComponent(prefix ? `${prefix}[${encodeURIComponent(key)}]` : encodeURIComponent(key));
      // queryParts.push(`${encodedKey}=`); // 加上等号
    }
    // 处理普通值
    else {
      const encodedKey = encodeURIComponent(prefix ? `${prefix}[${encodeURIComponent(key)}]` : encodeURIComponent(key));
      const encodedValue = encodeURIComponent(value);
      queryParts.push(`${encodedKey}=${encodedValue}`);
    }
  }
  
  return queryParts.join('&');
}
```

### 方法二：提供一个locutus库中 js 仿php http_build_query 写法
>修复了该库的方法在有空数组空对象时多出连续&符号问题

```js
function urldecode (str) {
  return decodeURIComponent((str + '')
    .replace(/%(?![\da-f]{2})/gi, function () {
      // PHP tolerates poorly formed escape sequences
      return '%25'
    })
    .replace(/\+/g, '%20'))
}

function rawurldecode (str) {
  return decodeURIComponent((str + '')
    .replace(/%(?![\da-f]{2})/gi, function () {
      // PHP tolerates poorly formed escape sequences
      return '%25'
    }))
}

function urlencode (str) {
  str = (str + '')
  return encodeURIComponent(str)
    .replace(/!/g, '%21')
    .replace(/'/g, '%27')
    .replace(/\(/g, '%28')
    .replace(/\)/g, '%29')
    .replace(/\*/g, '%2A')
    .replace(/~/g, '%7E')
    .replace(/%20/g, '+')
}

function rawurlencode (str) {
  str = (str + '')
  return encodeURIComponent(str)
    .replace(/!/g, '%21')
    .replace(/'/g, '%27')
    .replace(/\(/g, '%28')
    .replace(/\)/g, '%29')
    .replace(/\*/g, '%2A')
}

function http_build_query (formdata, numericPrefix, argSeparator, encType) {
  let encodeFunc

  switch (encType) {
    case 'PHP_QUERY_RFC3986':
      encodeFunc = rawurlencode
      break

    case 'PHP_QUERY_RFC1738':
    default:
      encodeFunc = urlencode
      break
  }

  let value
  let key
  const tmp = []

  var _httpBuildQueryHelper = function (key, val, argSeparator) {
    let k
    const tmp = []
    if (val === true) {
      val = '1'
    } else if (val === false) {
      val = '0'
    }
    if (val !== null) {
      if (typeof val === 'object') {
        for (k in val) {
          if (val[k] !== null) {
            // 判断是否为空数组或空对象
            if (Array.isArray(val[k]) && val[k].length === 0) {
              continue;
            }
            if (Object.prototype.toString.call(val[k]) === '[object Object]' && Object.keys(val[k]).length === 0) {
              continue;
            }
            tmp.push(_httpBuildQueryHelper(key + '[' + k + ']', val[k], argSeparator))
          }
        }
        return tmp.join(argSeparator)
      } else if (typeof val !== 'function') {
        return encodeFunc(key) + '=' + encodeFunc(val)
      } else {
        throw new Error('There was an error processing for http_build_query().')
      }
    } else {
      return ''
    }
  }

  if (!argSeparator) {
    argSeparator = '&'
  }
  for (key in formdata) {
    value = formdata[key]
    if (numericPrefix && !isNaN(key)) {
      key = String(numericPrefix) + key
    }
    const query = _httpBuildQueryHelper(key, value, argSeparator)
    if (query !== '') {
      tmp.push(query)
    }
  }

  return tmp.join(argSeparator)
}
```

### 提供一个js sortData 排序方法
```js
function sortData(data, sortOrder = "asc") {
  const compareFunction = (a, b) => {
    if (a === b) {
      return 0;
    }
    return sortOrder === "desc" ? (a > b ? -1 : 1) : (a < b ? -1 : 1);
  };

  if (Array.isArray(data)) {
    return Object.keys(data).sort(compareFunction).map((value) =>{
      value = data[value];
      return typeof value === "object" && value !== null
      ? sortData(value, sortOrder)
      : value
    });
  }

  if (typeof data === "object" && data !== null) {
    const sortedObject = {};
    const sortedKeys = Object.keys(data).sort(compareFunction);

    for (const key of sortedKeys) {
      sortedObject[key] =
        typeof data[key] === "object" && data[key] !== null
          ? sortData(data[key], sortOrder)
          : data[key];
    }

    return sortedObject;
  }

  return data;
}
```

### 提供一个php sortData 排序方法
```php
/**
 * 数据排序
 * @param array $data
 * @return array
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
```

## 测试js版本 http_build_query + sortData 对比 php版本 http_build_query + sortData
> js版本使用的是上面 http_build_query方法(上面) + sortData方法(上面) + urldecode方法(上面)

>php版本使用的http_build_query + sortData(上面) + urldecode
```js
console.log(urldecode(http_build_query(sortData({
  "n": "ewsqam3",
  "t": "1677933143",
  "a": "1661408635",
  "members": {
      "p":{},
      "o":[],
      "f": "",
      "a": null,
      "b": 1,
      "c": false,
      "d": true,
      "members": {
          "c": true,
          "a": 0,
          "b": false,
          "members": {
              "s": false,
              "e": true,
              "f": 0,
              "g": null,
              "j": ""
          }
      }
  }
}))))

// js结果
a=1661408635&members[b]=1&members[c]=0&members[d]=1&members[f]=&members[members][a]=0&members[members][b]=0&members[members][c]=1&members[members][members][e]=1&members[members][members][f]=0&members[members][members][j]=&members[members][members][s]=0&n=ewsqam3&t=1677933143

//php结果
a=1661408635&members[b]=1&members[c]=0&members[d]=1&members[f]=&members[members][a]=0&members[members][b]=0&members[members][c]=1&members[members][members][e]=1&members[members][members][f]=0&members[members][members][j]=&members[members][members][s]=0&n=ewsqam3&t=1677933143
```