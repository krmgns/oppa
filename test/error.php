<?php
include('inc.php');

$autoload = require(__dir__.'/../src/Autoload.php');
$autoload->register();

use Oppa\Database;

// $cfg = [
//     'agent' => 'mysqli',
//     'database' => [
//         'host' => 'localhost',
//         'name' => 'test',
//         'username' => 'test',
//         'password' => '********',
//     ],
// ];

// $db = Database($cfg);
// try {
//     $agent = $db->connect()->getConnection()->getAgent()->query('select * from nonexists');
// } catch (\Throwable $e) {
//     print $e->getMessage();
// }

$cfg = [
    'agent' => 'mysqli',
    'database' => [
        'host' => 'localhost',
        'name' => 'test',
        'username' => 'test',
        'password' => '********',
    ],
    'query_error_handler' => function($exception, $query, $queryParams) {
        print $exception->getMessage();
    }
];

$db = new Database($cfg);
$db->connect()->getConnection()->getAgent()->query('select * from nonexists');
