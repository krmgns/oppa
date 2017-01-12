<?php namespace App\Lib\Database;
include('_inc.php');

use Oppa\Database;

$cfg = [
    'agent'    => 'pgsql',
    'database' => [
        'host'     => 'localhost',  'name'     => 'test',
        'username' => 'test',       'password' => '********',
        'charset'  => 'utf8',       'timezone' => '+00:00',
    ],
    // 'map_result' => true,
    // 'map_result_bool' => true,
];

$db = new Database($cfg);
$db->connect();

$agent = $db->getLink()->getAgent();

class User {}

$result = $agent->query("select * from users", null, null, User::class);
pre($result->getData());


