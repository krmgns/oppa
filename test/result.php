<?php
include('inc.php');

$autoload = require('./../Oppa/Autoload.php');
$autoload->register();

use \Oppa\Database;
use \Oppa\Configuration;

$cfg = [
    'agent' => 'mysqli',
    // 'profiling' => true,
    'map_result' => true,
    'map_result_tiny2bool' => true,
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

$db = Database\Factory::build(new Configuration($cfg));
$db->connect();

$agent = $db->getConnection()->getAgent();

// $result = $agent->query("show tables");
// $result = $agent->query("describe users");
// $result = $agent->query("select * from information_schema.columns where table_schema = 'test'");

$result = $agent->query("select * from users u");
prd($result->getData());
pre($result);
// pre($agent,1);

// $result = $agent->query("select * from `users`");
// $result = $agent->query("select * from `users`");
// $result = $agent->query("select * from `users`");
// $result = $agent->query("select * from `users` where `id` = ?", [1]);
// $result = $agent->query("select * from `users` where `id` IN(?)", [[1,2]]);
// $result = $agent->query("select * from `users` where `id` IN(?,?)", [1,2]);
// pre($result,1);

// pre($result->count());
// foreach ($result as $user) {
//     pre($user->name);
// }

// $result = $agent->query("update `users` set `old` = 30 where `id`=?", [1]);
// pre($agent->rowsAffected());
// pre($result);

// $result = $agent->get("select * from `users` where `id` = ?", [1]);
// pre($result);
// $result = $agent->get("select * from `users`");
// pre($result);

// $result = $agent->getAll("select * from `users`");
// pre($result);
// $result = $agent->getAll("select * from `users` where `id` in(?,?)", [1,2]);
// pre($result);

// $agent->getAll("select * from `users`");
// prd($agent->rowsCount());

// pre($agent);
// pre($db);
