<?php
include('_inc.php');

use Oppa\Database;

$cfg = [
    'agent'    => 'pgsql',
    'database' => [
        'host'     => 'localhost',  'name'     => 'test',
        'username' => 'test',       'password' => '********',
        'charset'  => 'utf8',       'timezone' => '+00:00',
    ],
    // 'map_result' => true,
    // 'map_result_bool' => true,
];

$db = new Database($cfg);
$db->connect();

$agent = $db->getLink()->getAgent();
// pre($agent);
// $agent->disconnect();
// pre($agent->getResource()->isValid());

$result = $agent->query("select * from users");
// $result = $agent->query("insert into users(name,old) values('foo',10),('ffo',11)");
// $result = $agent->query("delete from users where id > 3");
// pre($result);
// pre($agent->getResource());
// pre($result->getResult());
// pre($result->getId());
// pre($result->getIds());
// pre($result->getData());

