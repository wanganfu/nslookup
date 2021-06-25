<?php

include_once __DIR__ . "/../vendor/autoload.php";

$domain = "_acme-challenge.www.avza.cn";

try {
    $res = \annon\GetTxtRecord::getTXTRecord($domain);
    var_dump($res);
} catch (Exception $e) {
    var_dump($e->getMessage());
}
