<?php
include('inc.php');

$autoload = require('./../Oppa/Autoload.php');
$autoload->register();

use \Oppa\Configuration;
use \Oppa\Database\Factory;

static $cfg = [
    'agent'    => 'mysqli',
    'database' => [
        'host'     => 'localhost',  'name'     => 'test',
        'username' => 'test',       'password' => '********',
        'charset'  => 'utf8',       'timezone' => '+00:00',
    ]
];

function db_init() {
    global $cfg;
    if (!isset($GLOBALS['$db'])) {
        $db = Factory::build(new Configuration($cfg));
        $db->connect();
        // store db
        $GLOBALS['$db'] = $db;
    }

    return $GLOBALS['$db'];
}

function db_query($sql, array $params = null) {
    // get database instance
    $db = db_init();
    return $db->getConnection()->getAgent()->query($sql, $params);
}

// make a regular query
$users = db_query("select * from `users` limit 3");
foreach ($users as $user) {
    print $user->name;
}
