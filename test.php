<?php
require_once 'vendor/autoload.php';

$target_dir = 'F:\code\test\\';
$con = 'F:\code\test';
$con = 'F:\code\test\\';
try {
    $doc = new \Doc\doc('test',$target_dir,$con,2);
    $base64 = $doc->init();
    var_dump($base64);exit;
}catch (\Doc\DocException $e){
    var_dump($e->getMessage());
}

