const CryptoJS = require("crypto-js");

class AES {
  constructor(key) {
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
const keyHex = '3ddc81a729c34c50b097a098b0512f16'; //秘钥必须为32位
const aes = AES.fromHex(keyHex);

// 加密使用示例
const plaintext = '你好，二蛋！';
const encrypted = aes.encrypt(plaintext);
console.log("加密内容：", encrypted);


// 解密使用示例
const str = aes.decrypt(encrypted);
console.log("解密内容：", str);