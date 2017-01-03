<?php
include('_inc.php');

use Oppa\Database;
use Oppa\Config;

/*** single ***/
$cfg = [
    'agent' => 'pgsql',
    'database' => [
        'fetch_type' => 'object',
        'charset'    => 'utf8',
        'timezone'   => '+00:00',
        // 'port'       => 3306,
        'host'       => 'localhost',
        'name'       => 'test',
        'username'   => 'test',
        'password'   => '********',
        // 'options' => [MYSQLI_OPT_CONNECT_TIMEOUT => 3], // mysql
        // 'options' => ['connect_timeout' => 3], // pgsql
    ],
    // 'map_result' => true,
    // 'map_result_bool' => true,
    'profile' => true,
];

$db = new Database($cfg);
$db->connect();

$agent = $db->getLink()->getAgent();

// $s = $agent->query("update users set booleeeee=false where id=3");
// $s = $agent->query("select * from users order by id asc limit %d", ["3"]);
// $s = $agent->query("insert into users (name,old) values ('Can',20)");
// $s = $agent->query("insert into users (name,old) values ('Can',20) returning currval('users_id_seq')");
// $s = $agent->query("insert into users (name,old) values ('a',20),('b',21)");
// $s = $agent->query("insert into users (name,old) values ('Can',20)");
// $s = $agent->count("select * from users where id > ? order by id", [10]);
// SELECT reltuples::bigint FROM pg_class WHERE oid = 'public.foo'::regclass
// $s = $agent->count("SELECT id FROM foo WHERE id < :id", ['id' => 10]);
// $s = $agent->count("users");
// $s = $agent->count(null, "select * from users");
// pre($s,1);
// pre($agent->getProfiler(),1);

// $b = $db->getLink()->getAgent()->getBatch();
// $b->lock();
// try {
//     $b->queue("delete from users where id > 4");
//     // $b->queue("insert into users (name,old) values ('a',20),('b',22)");
//     // $b->queue("insert into users (name,old) values ('c',200),('d',220)");
//     $b->queue("insert into users (name,old) values ('e',201)");
//     $b->do();
// } catch (\Oppa\Exception\QueryException $e) {
//     pre($e->getMessage());
//     $b->undo();
// }
// $b->unlock();
// pre($b->getResults());
// pre($b->getResultsIds());
// foreach ($b->getResults() as $r) {
//     pre($r->getIds());
// }

// $ii = [[154, 155], [156]];
// $id = [];
// pre($ii);
// foreach($ii as $i) $id = array_merge($id, $i);
// pre($id);
// $id=[];
// foreach($ii as $i) $id[] = $i;
// pre($id);

// $b = $db->getLink()->getAgent()->getBatch();
// pre($b);
// prd($s->getId());
// prd($s->getIds());

// pre($db);
// pre($db->getLink()->getAgent()->isConnected());
// pre($db->getLink()->getAgent()->queryMulti(["select * from foos limit 1"])); // @TODO
// pre($db->getLink()->getAgent()->query("select * from foos limit 1"));
// pre($db->getLink());
// pre($db->getLink('localhost'));

// // $db->disconnect();
// $db->disconnect('localhost');
// pre($db->getLink('localhost')); // err!

/*** sharding ***/
// $cfg = [
//     'agent' => 'mysql',
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
//         // 'options' => [MYSQLI_OPT_CONNECT_TIMEOUT => 3],
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
