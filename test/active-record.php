<?php
include('_inc.php');

use Oppa\Database;
use Oppa\Query\Builder;
use Oppa\ActiveRecord\ActiveRecord;

function db() {
    return new Database([
        'agent' => 'pgsql',
        'profiling' => true,
        'database' => [
            'host' => 'localhost', 'name' => 'test',
            'username' => 'test',  'password' => '********',
        ]
    ]);
}

class Users extends ActiveRecord {
    protected $table = 'users';
    protected $tablePrimary = 'id';

    public function __construct() {
        parent::__construct(db());
    }

    public function onFind(Builder $qb) {
        return $qb
            ->select('users.*')
            ->joinLeft('users_score', 'users_score.user_id = users.id')
            ->selectMore('sum(users_score.score) score')
            ->groupBy('users.id')
            ->orderBy('users.id')
        ;
    }

    public function onEntity($entity) {
        $entity->addMethod('getPageLink', function() use($entity) {
            return sprintf('<a href="user.php?id=%d">%s</a>', $entity->id, $entity->name);
        });
    }
}

$users = new Users();
// pre($users);

// $user = $users->find(1);
// // pre($user);
// pre($user->getPageLink(),1);
// // prd($user->isFound());

// // $users = $users->findAll();
// $users = $users->findAll([1,2]);
// // $users = $users->findAll('users.id in(?)', [[1,2]]);
// // $users = $users->findAll('users.id in(?,?)', [1,2]);
// // pre($users);
// foreach ($users as $user) {
//     pre($user->getPageLink());
// }
// $users = $users->findAll([-1,-2,-399]);
// prd($users->isFound());

// insert
// $user = $users->entity();
// $user->name = 'Deli';
// $user->old = rand(100,500);
// prd($user->save());
// pre($user);

// update
// $user = $users->entity();
// $user->id = 933;
// $user->name = 'Veli';
// $user->old = 55;
// prd($users->save($user));
// pre($user);

// update exists
// $user = $users->find(933);
// $user->old = 100;
// pre($user->save());

// remove exists
// $user = $users->entity();
// $user->id = 262;
// pre($user->remove());

// $user = $users->find(261);
// if ($user->isFound())
//     pre($user->remove());
// else
//     pre("nö!");

// remove
// $result = $users->removeAll(933);
// $result = $users->removeAll([258,259,260]);
// pre($result);
