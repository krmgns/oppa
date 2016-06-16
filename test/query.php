<?php
include('inc.php');

$autoload = require(__dir__.'/../src/Autoload.php');
$autoload->register();

use Oppa\Database;
use Oppa\Config;

$cfg = [
    'agent' => 'mysqli',
    'profiling' => true,
    'database' => [
        'fetch_type' => 'object',
        'charset'    => 'utf8',
        'timezone'   => '+00:00',
        'host'       => 'localhost',
        'name'       => 'test',
        'username'   => 'test',
        'password'   => '********',
    ]
];

$db = new Database($cfg);
$db->connect();

$agent = $db->getLink()->getAgent();
$agent->query("delete from `users` where `id` > ?", [3]);
pre($agent->rowsAffected());

pre($agent);
// pre($db);
