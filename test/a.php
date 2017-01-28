<?php
include('_inc.php');

use Oppa\Database;

$cfg = [
    'agent'    => 'mysql',
    'database' => [
        'host'     => 'localhost',  'name'     => 'test',
        'username' => 'test',       'password' => '********',
        'charset'  => 'utf8',       'timezone' => '+00:00',
    ]
];

$db = new Database($cfg);
$agent = $db->connect()->getLink()->getAgent();

// $s = $agent->prepare('sid = :sid, pid = :pid, a = ?, tid = :tid, b = %d', [
//     'aaa',
//     'pid' => 2,
//     'sid' => 1,
//     '9000',
//     'tid' => 3
// ]);
$s = $agent->prepare('SELECT * FROM %v WHERE id = %i', ['foo', 1]);
pre($s);
