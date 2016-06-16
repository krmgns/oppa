<?php
include('inc.php');

$autoload = require(__dir__.'/../src/Autoload.php');
$autoload->register();

use Oppa\Database;
use Oppa\Config;

/*** single ***/
$cfg = [
    'agent' => 'mysqli',
    'database' => [
        'fetch_type' => 'object',
        'charset'    => 'utf8',
        'timezone'   => '+00:00',
        'port'       => 3306,
        'host'       => 'localhost',
        'name'       => 'test',
        'username'   => 'test',
        'password'   => '********',
        'profile'   => true,
        'query_log'=>true,
        'query_log_level' => Oppa\Logger::ALL,
        'query_log_directory' => __dir__.'/../.logs',
        // 'connect_options' => ['mysqli_opt_connect_timeout' => 3],
    ]
];

$db = new Database($cfg);
$db->connect();
// pre($db);
pre($db->getLink()->getAgent()->query("select * from users"));
// pre($db->getLink());
// pre($db->getLink('localhost'));

// // $db->disconnect();
// $db->disconnect('localhost');
// pre($db->getLink('localhost')); // err!

/*** sharding ***/
// $cfg = [
//     'agent' => 'mysqli',
//     'sharding' => true,
//     'database' => [
//         'fetch_type' => 'object',
//         'charset'    => 'utf8',
//         'timezone'   => '+00:00',
//         'port'       => 3306,
//         'username'   => 'test',
//         'password'   => '********',
//         'master'     => ['host' => 'master.mysql.local', 'name' => 'test', 'port' => 3307],
//         'slaves'     => [
//             ['host' => 'slave1.mysql.local', 'name' => 'test'],
//             ['host' => 'slave2.mysql.local', 'name' => 'test'],
//             ['host' => 'slave3.mysql.local', 'name' => 'test'],
//         ],
//         // 'connect_options' => ['mysqli_opt_connect_timeout' => 3],
//     ]
// ];

// $db = new Database($cfg);

// // for master connection
// $db->connect();
// $db->connect('master');
// $db->connect('master.mysql.local');

// // for slaves connection
// // - if empty, then connects to master
// // - so must be indicated as "slaves" or "slave.host.*"
// $db->connect('slave'); // random
// $db->connect('slave1.mysql.local');
// $db->connect('slave2.mysql.local');
// $db->connect('slave3.mysql.local');
// $db->connect('slave3.mysql.local'); // no more try to connect
// $db->connect('slave3.mysql.local'); // no more try to connect
// $db->connect('slave3.mysql.local'); // no more try to connect

// $db->disconnect('master');
// $db->disconnect('slave');
// $db->disconnect('*');
// pre($db);

// pre($db->getLink());
// pre($db->getLink('master'));
// pre($db->getLink('master.mysql.local'));

// pre($db->getLink('slave'));
// pre($db->getLink('slave1.mysql.local'));
// pre($db->getLink('slave2.mysql.local'));
// pre($db->getLink('slave3.mysql.local'));
