<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Wengg\WebmanApiSign\Encryption\AES;

$aes = new AES('D81668E7B3F24F4DAB32E5B88EAE27AC');

$str_padded = '{"name":"tangfei"}';
$data = $aes->encrypt($str_padded);

echo $data;

echo "\n";

$data2 = $aes->decrypt($data);

echo $data2;