<?php
require_once __DIR__ . '/../vendor/autoload.php';

$header = '{
    "appId": "1661408635",
    "nonceStr": "ewsqam3",
    "timestamp": "1677933143"
}';

$body = '{
    "name": "tangfei"
}';

$app_secret = 'D81668E1B3F24F4DAB32E5B88EAE27AC';

$header = json_decode($header,true);
$body = json_decode($body,true);
$data = array_merge($header, $body);

function sortData(array &$data)
{
    $sort = function (array &$data) use (&$sort) {
        ksort($data);
        foreach ($data as &$value) {
            if (is_array($value)) {
                $sort($value);
            }
        }
    };
    $sort($data);
}

sortData($data);

$data = urldecode(http_build_query($data)) . $app_secret;

echo $data;
echo "\n";

$signature = hash('sha256', $data);
echo $signature;