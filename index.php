<?php

require_once 'bootstrap.php';

$dir = __DIR__ . '/vendor';

$master = new Oksis_Master($dir);
$master->createDirectories();
//
//$google = new Oksis_GoogleFacade();
//$google->uploadDir('vendor');
//$google->uploadFile('vendor/autoload.php');