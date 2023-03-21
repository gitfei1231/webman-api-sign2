<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Wengg\WebmanApiSign\Encryption\AES;

$aes = new AES('3ddc81a729c34c50b097a098b0512f16'); //秘钥必须为32位

$str_padded = '{"name":"tangfei"}';
$data = $aes->encrypt($str_padded);

echo $data;

echo "\n";

$data2 = $aes->decrypt($data);

echo $data2;