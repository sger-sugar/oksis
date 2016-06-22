<?php

require_once 'bootstrap.php';

$dir = __DIR__ . '/vendor';
//$dir = '/var/www/firma-rukodelie.ru';

$treadCount = 3;

$master = new Oksis_Master($dir, $treadCount);
//exit;
$master->createDirectories();
exit;
echo 'ALL DIRECTORIES CREATED' . PHP_EOL;

$master->uploadFiles();