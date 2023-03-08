<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Wengg\WebmanApiSign\Encryption\DES;

$des = new DES('D81668E7B3F24F4DAB32E5B88EAE27AC');

$str_padded = '{"name":"tangfei"}';
$data = $des->encrypt($str_padded);

echo $data;

echo "\n";

$data2 = $des->decrypt($data);

echo $data2;