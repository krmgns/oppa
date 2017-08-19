<?php
include('_inc.php');

use Oppa\Database;

$cfg = [
    'agent' => 'mysql',
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

$db = new Database($cfg);
$db->connect();

$agent = $db->getLink()->getAgent();

// $result = $agent->select('users', ['id','name']);
// $result = $agent->selectAll('users', ['id','name']);
// $result = $agent->insert('users', ['name' => 'Ferhat', 'old' => 50]);
// $result = $agent->insertAll('users', [['name' => 'Ferhat', 'old' => 50],['name' => 'Serhat', 'old' => 60]]);
// $result = $agent->update('users', ['name' => 'Veli', 'old' => 60], 'id = ?', [4]);
// $result = $agent->updateAll('users', ['name' => 'Veli', 'old' => 60], 'id > ?', [4]);
// $result = $agent->delete('users', 'id = ?', [4]);
// $result = $agent->deleteAll('users', 'id in (?,?)', [5,6]);
// pre($result);

pre($result);
// pre($db);
