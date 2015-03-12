<?php
include('inc.php');

$autoload = require('./../Oppa/Autoload.php');
$autoload->register();

use \Oppa\Database;
use \Oppa\Configuration;

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

$db = Database\Factory::build(new Configuration($cfg));
$db->connect();

$agent = $db->getConnection()->getAgent();
$agent->query("delete from `users` where `id` > ?", [30000]);
$agent->query("delete from `users` where `id` > ?", [30000]);

pre($agent->getProfiler());
// pre($db);

$profiler = $agent->getProfiler();
// $profiler->reset();

pre($profiler->lastQuery);
pre($profiler->queryCount);
pre($profiler->getLastQuery());
pre($profiler->getQueryCount());
pre($profiler->getProfile($profiler::CONNECTION));
pre($profiler->getProfile($profiler::LAST_QUERY));
