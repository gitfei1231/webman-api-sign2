// 引入第三方库
const UUID = require('uuid');
const cryptoJs = require("crypto-js");
const rs = require('jsrsasign');

// 获取预先设置为环境变量的
const is_rsa = pm.environment.get("is_rsa");
const public_key = pm.environment.get("public_key");
let app_secret = pm.environment.get("app_secret");
const app_id = pm.environment.get("app_id");
const encrypt_body = pm.environment.get("encrypt_body");
// 获取10位时间戳
const timestamp = Math.round(new Date().getTime() / 1000).toString();
// 生成随机noncestr
const noncestr = UUID.v4().replace(/-/g, '');

//判断如果开启rsa，则先客户端生成app_secret,在用这个app_secret进行加密sign
if (is_rsa == 1) {
  app_secret = UUID.v4().replace(/-/g, '');// 转为32位秘钥

  // 解析公钥，将字符串转换为 KeyObject 对象
  const publicKeyObj = rs.KEYUTIL.getKey(public_key);
  // 使用公钥加密数据 算法 'RSAES-PKCS1-V1_5'
  const encryptedData = publicKeyObj.encrypt(app_secret);
  // 将加密后的数据转换成 Base64 编码
  const base64CipherText = rs.hex2b64(encryptedData);
  // 添加到头
  pm.request.headers.upsert({ key: 'k', value: base64CipherText }); //app_key
}

// 生成URL编码后的查询字符串
class URLTools {
  static sortData(data, sortOrder = "asc") {
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
        ? this.sortData(value, sortOrder)
        : value
      });
    }
  
    if (typeof data === "object" && data !== null) {
      const sortedObject = {};
      const sortedKeys = Object.keys(data).sort(compareFunction);
  
      for (const key of sortedKeys) {
        sortedObject[key] =
          typeof data[key] === "object" && data[key] !== null
            ? this.sortData(data[key], sortOrder)
            : data[key];
      }
  
      return sortedObject;
    }
  
    return data;
  }
  
  static urldecode (str) {
    return decodeURIComponent((str + '')
      .replace(/%(?![\da-f]{2})/gi, function () {
        // PHP tolerates poorly formed escape sequences
        return '%25'
      })
      .replace(/\+/g, '%20'))
  }
  
  static rawurldecode (str) {
    return decodeURIComponent((str + '')
      .replace(/%(?![\da-f]{2})/gi, function () {
        // PHP tolerates poorly formed escape sequences
        return '%25'
      }))
  }
  
  static urlencode (str) {
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
  
  static rawurlencode (str) {
    str = (str + '')
    return encodeURIComponent(str)
      .replace(/!/g, '%21')
      .replace(/'/g, '%27')
      .replace(/\(/g, '%28')
      .replace(/\)/g, '%29')
      .replace(/\*/g, '%2A')
  }
  
  static http_build_query (formdata, numericPrefix, argSeparator, encType) {
    let encodeFunc
  
    switch (encType) {
      case 'PHP_QUERY_RFC3986':
        encodeFunc = this.rawurlencode
        break
  
      case 'PHP_QUERY_RFC1738':
      default:
        encodeFunc = this.urlencode
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
}

//AES对称加密
class AES {
  constructor(key) {
    this.key = key;
    this.method = "aes-128-cbc";
  }

  encrypt(plaintext) {
    const iv = cryptoJs.lib.WordArray.random(16);
    const ciphertext = cryptoJs.AES.encrypt(
      plaintext,
      this.key,
      { iv: iv, padding: cryptoJs.pad.Pkcs7, mode: cryptoJs.mode.CBC }
    );
    return iv.concat(ciphertext.ciphertext).toString(cryptoJs.enc.Base64);
  }

  decrypt(ciphertext) {
    ciphertext = cryptoJs.enc.Base64.parse(ciphertext);
    const iv = ciphertext.clone();
    iv.sigBytes = 16;
    iv.clamp();
    ciphertext.words.splice(0, 4); // remove IV from ciphertext
    ciphertext.sigBytes -= 16;
    const decrypted = cryptoJs.AES.decrypt(
      { ciphertext: ciphertext },
      this.key,
      { iv: iv, padding: cryptoJs.pad.Pkcs7, mode: cryptoJs.mode.CBC }
    );
    const plaintext = decrypted.toString(cryptoJs.enc.Utf8);
    return plaintext;
  }

  toHex() {
    return this.key.toString(cryptoJs.enc.Hex);
  }

  static fromHex(hexString) {
    return new AES(cryptoJs.enc.Hex.parse(hexString));
  }

  static fromBase64(base64String) {
    return new AES(cryptoJs.enc.Base64.parse(base64String));
  }

  toBase64() {
    return this.key.toString(cryptoJs.enc.Base64);
  }
}

// 存放所有需要用来签名的参数，
const param = {
  a: app_id,
  t: timestamp,
  n: noncestr
};

// 加入 query 参数
// let queryParams = pm.request.url.query
// queryParams.each((item) => {
//   if (!item.disabled) {
//     // 启用的参数才参与签名
//     param[item.key] = item.value
//   }
// })

// 加入 body 参数
if (pm.request.body && pm.request.body.mode === 'raw') {
  // 如果没有 JSON 格式的请求 body，或 JSON 格式 body 不参与签名，可以删除这一段
  let contentType = pm.request.headers.get('content-type')
  if (
    contentType &&
    pm.request.body.raw &&
    contentType.toLowerCase().indexOf('application/json') !== -1
  ) {
    let jsonData = JSON.parse(pm.request.body.raw);
    for (let key in jsonData) {
      param[key] = jsonData[key];
    }
  }

  // 判断是否加密报文
  if (encrypt_body == 1 && pm.request.body.raw) {
    const aes = AES.fromHex(app_secret);
    const encrypted = aes.encrypt(pm.request.body.raw);
    pm.request.body.update(encrypted);
  }
}

//重置 headers 中参与签名的必要参数t、n、s
pm.request.headers.upsert({ key: 't', value: timestamp }); //timestamp
pm.request.headers.upsert({ key: 'n', value: noncestr }); //nonceStr

//排序生成sign
const sortedData = URLTools.sortData(param);
const str = URLTools.urldecode(URLTools.http_build_query(sortedData)) + app_secret;
const signature = cryptoJs.SHA256(str).toString();
pm.request.headers.upsert({ key: 's', value: signature }); //signature

