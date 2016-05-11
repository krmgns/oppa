<?php
include('inc.php');

$autoload = require('./../Oppa/Autoload.php');
$autoload->register();

use Oppa\Logger;

$logger = new Logger();
$logger->setLevel(Logger::ALL);
$logger->setDirectory(__dir__.'/../.logs/test');

$result = $logger->log(Logger::INFO, 'log...');
prd($result);
