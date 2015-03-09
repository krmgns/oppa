<?php
include('inc.php');

$autoload = require('./../Oppa/Autoload.php');
$autoload->register();

use \Oppa\Database;
use \Oppa\Configuration;

$cfg = [
    'agent'    => 'mysqli',
    'database' => [
        'host'     => 'localhost',  'name'     => 'test',
        'username' => 'test',       'password' => '********',
        'charset'  => 'utf8',       'timezone' => '+00:00',
    ]
];

$db = Database\Factory::build(new Configuration($cfg));
$agent = $db->connect()->getConnection()->getAgent();

$s = $agent->prepare('sid = :sid, pid = :pid, a = ?, tid = :tid, b = %d', [
    'pid' => 2,
    'sid' => 1,
    'aaa',
    '9000',
    'tid' => 3
]);
pre($s);
