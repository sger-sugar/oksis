<?php

require_once 'bootstrap.php';

$dir = __DIR__ . '/vendor';
//$dir = '/var/www/firma-rukodelie.ru';

$treadCount = 3;

$master = new Oksis_Master($dir, $treadCount);
$master->createDirectories();
echo 'ALL DIRECTORIES CREATED' . PHP_EOL;
$forkId = $master->forkThreads();
if ($forkId == Oksis_Master::MASTER_FORK_ID) {
    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . $forkId . '.txt', 'pid');
} else {
    $log = $master->uploadFiles();
    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . $forkId . '.txt', $log);
}
