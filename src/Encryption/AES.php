<?php
namespace Wengg\WebmanApiSign\Encryption;

class AES
{
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