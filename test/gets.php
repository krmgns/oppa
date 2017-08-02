<?php namespace App\Lib\Database;
include('_inc.php');

use Oppa\Database;

$cfg = [
    'agent'    => 'mysql',
    'database' => [
        'host'     => 'localhost',  'name'     => 'test',
        'username' => 'test',       'password' => '********',
        'charset'  => 'utf8',       'timezone' => '+00:00',
    ],
    // 'fetch_type' => User::class,
    // 'fetch_limit' => 1,
    'map_result' => true,
    'map_result_bool' => true,
];

$db = new Database($cfg);
$db->connect();

$agent = $db->getLink()->getAgent();

class User {}

// $result = $agent->query("select * from users");
// $result = $agent->query("select * from users", null, 1, User::class);
$result = $agent->get("select * from users", null, User::class);
$result = $agent->getAll("select * from users", null, User::class);
pre($result);


