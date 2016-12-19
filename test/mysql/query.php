<?php
include('_inc.php');

use Oppa\Database;
use Oppa\Config;

$cfg = [
    'agent' => 'mysql',
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
