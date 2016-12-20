<?php
include('_inc.php');

use Oppa\Database;
use Oppa\Config;

$cfg = [
    'agent' => 'mysql',
    'profile' => true,
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
$agent->query("delete from `users` where `id` > ?", [30000]);
$agent->query("delete from `users` where `id` > ?", [40000]);

pre($agent->getProfiler());
// pre($db);

$profiler = $agent->getProfiler();
// $profiler->reset();

pre($profiler->getLastQuery());
// pre($profiler->getQueryCount());
// pre($profiler->getProfile($profiler::CONNECTION));
// pre($profiler->getProfile($profiler::QUERY));
