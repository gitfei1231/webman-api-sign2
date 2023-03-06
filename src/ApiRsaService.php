<?php

namespace Wengg\WebmanApiSign;
use phpseclib3\Crypt\RSA;

class ApiRsaService
{
    /**
     * rsa加密
     * @author mosquito <zwj1206_hi@163.com> 2022-08-25
     */
    public static function rsa_encode(string $content, string $public_key)
    {
        if (empty($content)) {
            throw new ApiSignException("加密内容为空", ApiSignException::CONTENT_EMPTY);
        }

        if (empty($public_key)) {
            throw new ApiSignException("加密key为空", ApiSignException::KEY_EMPTY);
        }
        
        $publicKey = RSA::loadPublicKey($public_key);
        $encodeStr = base64_encode($publicKey->encrypt($content));
        
        return $encodeStr;
    }

    /**
     * rsa解密
     * @author mosquito <zwj1206_hi@163.com> 2022-08-25
     */
    public static function rsa_decode(string $encodeStr, string $key)
    {
        if (empty($encodeStr)) {
            throw new ApiSignException("解密内容为空", ApiSignException::CONTENT_EMPTY);
        }

        if (empty($key)) {
            throw new ApiSignException("解密key为空", ApiSignException::KEY_EMPTY);
        }
        
        $privateKey = RSA::loadPrivateKey($key);
        $content = base64_decode($encodeStr);
        $decodeStr = $privateKey->decrypt($content);
        
        return $decodeStr;
    }

    /**
     * 创建公钥 私钥
     * @author mosquito <zwj1206_hi@163.com> 2022-08-25
     */
    public static function rsa_create()
    {
        $private = RSA::createKey();
        $public = $private->getPublicKey();
        
        return [
            'private' => $private,
            'public'  => $public
        ];
    }
}
