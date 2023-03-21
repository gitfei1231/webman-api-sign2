<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Wengg\WebmanApiSign\Encryption\AES;

$aes = new AES('0123456789abcdef0123456789abcdef');

$str_padded = '{"name":"tangfei"}';
$data = $aes->encrypt($str_padded);

echo $data;

echo "\n";

$data2 = $aes->decrypt($data);

echo $data2;