<?php
include('inc.php');

$autoload = require(__dir__.'/../src/Autoload.php');
$autoload->register();

use Oppa\Database;

static $cfg = [
    'agent'    => 'mysql',
    'database' => [
        'host'     => 'localhost',  'name'     => 'test',
        'username' => 'test',       'password' => '********',
        'charset'  => 'utf8',       'timezone' => '+00:00',
    ]
];

function db_init() {
    global $cfg;
    if (!isset($GLOBALS['$db'])) {
        $db = new Database($cfg);
        $db->connect();
        // store db
        $GLOBALS['$db'] = $db;
    }

    return $GLOBALS['$db'];
}

function db_query($sql, array $params = null) {
    // get database instance
    $db = db_init();
    return $db->getLink()->getAgent()->query($sql, $params);
}

// make a regular query
$users = db_query("select * from `users` limit 3");
foreach ($users as $user) {
    print $user->name;
}
