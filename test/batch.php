<?php
include('inc.php');

$autoload = require(__dir__.'/../src/Autoload.php');
$autoload->register();

use Oppa\Logger;
use Oppa\Database;

$cfg = [
    'agent' => 'mysqli',
    // 'profiling' => true,
    'query_log' => true,
    'query_log_level' => Logger::FAIL,
    'query_log_directory' => __dir__.'/../.logs',
    'query_log_filename_format' => 'Y-m-d',
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

// @tmp
// $db->getLink()->getAgent()->query('delete from users where id > 10');

$batch = $db->getLink()->getAgent()->getBatch();
// set autocommit=1
$batch->lock();
try {
    $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into users (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // $batch->queue('insert into userssssss (name,old) values (?,?)', ['John Doe', rand(1,100)]);
    // commit
    $batch->run();
} catch (\Throwable $e) {
    print $e->getMessage();
    // rollback & set autocommit=1
    $batch->cancel();
}
// set autocommit=1
$batch->unlock();

foreach ($batch->getResults() as $result) {
    print $result->getId() ."\n";
}

// $batch->reset();
pre($batch);
// pre($db);
