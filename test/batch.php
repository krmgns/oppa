<?php
include('_inc.php');

use Oppa\Logger;
use Oppa\Database;

$cfg = [
    'agent' => 'mysql',
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
try {
    $db->connect();
} catch(\Throwable $e) {
    print $e->getMessage() ."\n";
    print $e->getCode() ."\n";
    print $e->getSqlState() ."\n";
    // throw $e;
    return;
}

$agent = $db->getLink()->getAgent();

// @tmp
// $agent->query('delete from users where id > 3');
// $agent->query("select * from noneexists");

// pre($agent->getResourceStats(), 1);

$batch = $agent->getBatch();
// set autocommit=1
$batch->lock();
try {
    $agent->query("select * from noneexists");
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old!) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into userssssss (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // commit
    $batch->do();
// } catch (Oppa\Exception\QueryException $e) {
} catch (Throwable $e) {
    print $e->getMessage() ."\n";
    print $e->getCode() ."\n";
    print $e->getSqlState() ."\n";
    // rollback
    $batch->undo();
    // throw $e;
    return;
}
// set autocommit=1
$batch->unlock();

pre($batch->getResultsIds());
foreach ($batch->getResults() as $result) {
    print $result->getId() ."\n";
}

// $batch->reset();
// pre($batch);
// pre($db);
