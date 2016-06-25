<?php

require_once 'bootstrap.php';

$dir = __DIR__ . '/vendor';
//$dir = '/var/www/firma-rukodelie.ru';

$treadCount = 3;

$master = new Oksis_Master($dir, $treadCount);
$directories = $master->createDirectories();
exit(json_encode($directories));