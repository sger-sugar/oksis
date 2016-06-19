<?php

require_once 'bootstrap.php';

$master = new Oksis_Master();

$google = new Oksis_GoogleFacade();
$google->uploadDir('vendor');
$google->uploadFile('vendor/autoload.php');