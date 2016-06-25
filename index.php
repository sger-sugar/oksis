<?php

require_once 'bootstrap.php';

$dir = __DIR__ . '/vendor';
//$dir = '/var/www/firma-rukodelie.ru';

$treadCount = 3;

$output = array();
$result = exec('php ./directory.php', $output); // create directories in another process because of libcurl inner bug
$directories = json_decode($result, true);
if (!is_array($directories)) {
    exit($result);
}

echo 'ALL DIRECTORIES CREATED at ' . date('Y-m-d H:i:s') . PHP_EOL;

$master = new Oksis_Master($dir, $treadCount);
$master->setDirectories($directories);
$master->prepareFiles();

$forkId = $master->forkThreads();
if ($forkId == Oksis_Master::MASTER_FORK_ID) {
    $status = null;
    pcntl_wait($status);
    echo 'ALL FILES UPLOADED at ' . date('Y-m-d H:i:s') . PHP_EOL;
    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . $forkId . '.txt', 'pid');
} else {
    $log = $master->uploadFiles();
    file_put_contents(__DIR__ . DIRECTORY_SEPARATOR . $forkId . '.txt', $log);
}
