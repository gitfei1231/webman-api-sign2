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
const encryptedData = publicKeyObj.encrypt(data);

// 将加密后的数据转换成 Base64 编码
const base64CipherText = rs.hex2b64(encryptedData);

// 输出加密后的数据
console.log(`加密后的数据：${base64CipherText}`);

// 将 Base64 编码的密文还原成二进制数据
const binaryCiphertext = rs.b64tohex(base64CipherText);

// 解析私钥，将字符串转换为 KeyObject 对象
const privateKeyObj = rs.KEYUTIL.getKey(private_key);

// 使用私钥解密数据
const decryptedData = privateKeyObj.decrypt(binaryCiphertext);

// 输出解密后的数据
console.log(`解密后的数据：${decryptedData}`);
