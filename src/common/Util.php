<?php

namespace Wengg\WebmanApiSign\common;
use phpseclib3\Crypt\RSA;

class Util
{
    /**
     * openssl_encrypt 对称加密
     * @author tangfei <957987132@qq.com> 2023-03-07
     */
    public static function encrypt(string $data, string $key, string $method = 'AES-128-CBC')
    {
        if (empty($data)) {
            throw new ApiSignException("加密内容为空", ApiSignException::CONTENT_EMPTY);
        }
        
        if (empty($key)) {
            throw new ApiSignException("加密key为空", ApiSignException::KEY_EMPTY);
        }

        $ivlen = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        
        return openssl_encrypt($data, $method, $key, 0, $iv);
    }

    /**
     * openssl_encrypt  对称解密
     * @author tangfei <957987132@qq.com> 2023-03-07
     */
    public static function decrypt(string $data, string $key, string $method = 'AES-128-CBC')
    {
        if (empty($data)) {
            throw new ApiSignException("解密内容为空", ApiSignException::CONTENT_EMPTY);
        }

        if (empty($key)) {
            throw new ApiSignException("解密key为空", ApiSignException::KEY_EMPTY);
        }
        
        $ivlen = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivlen);

        return openssl_decrypt($data, $method, $key, 0, $iv);
    }


    /**
     * rsa加密
     * @author tangfei <957987132@qq.com> 2023-03-07
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
     * @author tangfei <957987132@qq.com> 2023-03-07
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
     * @author tangfei <957987132@qq.com> 2023-03-07
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
