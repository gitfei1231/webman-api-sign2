<?php
namespace Wengg\WebmanApiSign\Encryption;

class DES
{
    private $method = 'DES-CBC';
    private $key;

    public function __construct($key)
    {
        // 密钥长度不能超过64bit(UTF-8下为8个字符长度),超过64bit不会影响程序运行,但有效使用的部分只有64bit,多余部分无效,可通过openssl_error_string()查看错误提示
        $this->key = $key;
    }

    /**
     * 加密
     * @author tangfei <957987132@qq.com> 2023-03-07
     * @param string $plaintext 加密内容
     * @return string
     */
    public function encrypt($plaintext)
    {
        // 生成加密所需的初始化向量, 加密时缺失iv会抛出一个警告
        $ivlen = openssl_cipher_iv_length($this->method);
        $iv = openssl_random_pseudo_bytes($ivlen);

        // 加密数据. 如果options参数为0, 则不再需要上述的填充操作. 如果options参数为1, 也不需要上述的填充操作, 但是返回的密文未经过base64编码. 如果options参数为2, 虽然PHP说明是自动0填充, 但实际未进行填充, 必须需要上述的填充操作进行手动填充. 上述手动填充的结果和options为0和1是自动填充的结果相同.
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
        // 从密文中获取iv
        $ivlen = openssl_cipher_iv_length($this->method);
        $iv = substr($ciphertext, 0, $ivlen);
        $ciphertext = substr($ciphertext, $ivlen);

        // 解密数据
        $plaintext = openssl_decrypt($ciphertext, $this->method, $this->key, 1, $iv) ?? false;
        return $plaintext;
    }
}