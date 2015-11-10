<?php
include('inc.php');

$autoload = require('./../Oppa/Autoload.php');
$autoload->register();

use \Oppa\Database;
use \Oppa\Configuration;
use \Oppa\Database\Query\Builder as QueryBuilder;

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
    ]
];

$db = Database\Factory::build(new Configuration($cfg));
$db->connect();

$qb = new QueryBuilder($db->getConnection());
$qb->setTable('users');

// $qb->select('id,name');
// $qb->select('id,name')->where('id=?', [1]);
// $qb->select('id,name')->where('id=?', [1])->limit(1);
// $qb->select('id,name')->whereLike('name LIKE ?', ['%Ker"em%']);
// $qb->select('id,name')->whereLike('(id LIKE ? OR name LIKE ?)', ['2%', '%Ke_rem%']);

// $qb->select('id,name')
//     ->where('id=?', [1])
//     ->where('(name=? OR name=? OR old BETWEEN %d AND %d)', ['Kerem', 'Murat', 30, 40], $qb::OP_AND)
// ;

// $qb->select()->aggregate('count');
// pre($qb->get());

// $qb->setTable('users u');
// $qb->select('u.*, us.score, ul.login')
//     ->aggregate('sum', 'us.score', 'sum_score')
//     ->join('users_score us', 'us.user_id=u.id')
//     ->joinLeft('users_login ul', 'ul.user_id=u.id')
//     ->where('u.id in(?,?,?)', [1,2,3])
//     ->whereBetween('u.old', [30,50])
//     ->whereNotNull('ul.login')
//     ->groupBy('u.id')
//     ->orderBy('old')
//     // ->having('sum_score <= ?', [30])
//     ->limit(0,10)
// ;

// pre($qb->toString());
// pre($qb->get());
// pre($qb->getAll());
// pre($qb->execute());

// insert
// $qb->insert(['name' => 'Veli', 'old' => 25]);
// $qb->insert([['name' => 'Veli', 'old' => 25], ['name' => 'Deli', 'old' => 29]]);
// pre($qb->toString());
// $result = $qb->execute();
// pre($result);
// pre($result->getId());
// pre($result->getId(true));

// // // update
// $qb->update(['old' => 100])->where('id > ?', [30])->limit(1);
// $qb->update(['old' => 100])->where('id > ?', [30])->orderBy('id DESC')->limit(1);
// pre($qb->toString());
// pre($qb->execute());

// // delete
// $qb->delete()->where('id > ?', [30])->limit(1);
// $qb->delete()->where('id > ?', [30])->orderBy('id DESC')->limit(1);
// $qb->delete()->where('id > ?', [30])->orderBy('id', $qb::OP_DESC)->limit(1);
// $qb->delete()->whereBetween('id', [931,932])->limit(10);
// $qb->delete()->where('id in(?)', [[931,932]])->limit(10);
// pre($qb->toString());
// pre($qb->execute());

// $qb->select('id,name');
// $qb->whereLessThan('id', 30);
// $qb->whereGreaterThan('id', 20);
// $qb->whereLessThanEqual('id', 30, 'OR');
// $qb->whereGreaterThanEqual('id', 20);

// $qb->whereExists(
//     (new QueryBuilder($db->getConnection(), 'foo'))
//         ->select('*')
//         ->where('y > ?')
//     , [10]);
// $qb->whereExists('select * from foo where y > ?', [10]);
// $qb->whereExists('select * from foo where y < ?', [20], 'OR');

prd($qb->toString());
pre($qb);
// pre($db);
