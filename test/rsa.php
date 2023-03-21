<?php
require_once __DIR__ . '/../vendor/autoload.php';

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
echo $data['public_key']."\n\n\n";

///=================================下面是解密 加密=================================///

// 私钥，可以使用 上面生成 $data['private_key']
$private_key = $data['private_key'];

// 公钥，可以使用 上面生成 $data['public_key']
$public_key = $data['public_key'];

// 加密内容
$str1 = "你好，二蛋！";

$str2 = $rsa->rsa_encode($str1, $public_key);

echo "加密内容：\n";
echo $str2;
echo "\n\n\n";

$res = $rsa->rsa_decode($str2, $private_key);

echo "解密内容：\n";
echo $res;
echo "\n\n\n";