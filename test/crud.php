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
        // 'connect_options' => ['mysqli_opt_connect_timeout' => 3],
    ]
];

$db = Database\Factory::build(new Configuration($cfg));
$db->connect();

$agent = $db->getConnection()->getAgent();

$result = $agent->select('users', ['id','name']);
// $result = $agent->insert('users', ['name' => 'Ferhat', 'old' => 50]);
// $result = $agent->insert('users', [['name' => 'Ferhat', 'old' => 50],['name' => 'Serhat', 'old' => 60]]);
// $result = $agent->update('users', ['name' => 'Veli', 'old' => 60], 'id=?', [6]);
// $result = $agent->delete('users', 'id=?', [6]);
// $result = $agent->delete('users', 'id in (?,?,?)', [4,5,6]);

// pre($result);

pre($agent);
// pre($db);
