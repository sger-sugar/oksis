<?php

require_once 'bootstrap.php';

$dir = __DIR__ . '/vendor';

$master = new Oksis_Master($dir);
$master->createDirectories();
$master->uploadFiles();