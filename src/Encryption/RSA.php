<?php

namespace Wengg\WebmanApiSign\Encryption;
use phpseclib3\Crypt\RSA as CryptRSA;

class RSA
{
    /**
     * rsa加密
     * @author tangfei <957987132@qq.com> 2023-03-07
     * @param string $content 加密内容
     * @param string $public_key 公钥
     * @return string
     */
    public static function rsa_encode(string $content, string $public_key)
    {
        $publicKey = CryptRSA::loadPublicKey($public_key)->withPadding(CryptRSA::ENCRYPTION_PKCS1);
        $encodeStr = base64_encode($publicKey->encrypt($content));
        
        return $encodeStr;
    }

    /**
     * rsa解密
     * @author tangfei <957987132@qq.com> 2023-03-07
     * @param string $encodeStr 解密内容
     * @param string $private_key 私钥
     * @return string
     */
    public static function rsa_decode(string $encodeStr, string $private_key)
    {
        $privateKey = CryptRSA::loadPrivateKey($private_key)->withPadding(CryptRSA::ENCRYPTION_PKCS1);
        $content = base64_decode($encodeStr);
        $decodeStr = $privateKey->decrypt($content);
        
        return $decodeStr;
    }

    /**
     * 创建公钥 私钥
     * @author tangfei <957987132@qq.com> 2023-03-07
     * @return array
     */
    public static function rsa_create()
    {
        $private = CryptRSA::createKey();
        $public = $private->getPublicKey();
        
        return [
            'private_key' => $private,
            'public_key'  => $public
        ];
    }
}
