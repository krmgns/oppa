<?php
include('inc.php');

$autoload = require(__dir__.'/../src/Autoload.php');
$autoload->register();

use Oppa\Database;
use Oppa\ActiveRecord\ActiveRecord;

function db() {
    return new Database([
        'agent' => 'mysql',
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

    public function onFind($query) {
        return $query
            ->select('users.*')
            ->joinLeft('users_score', 'users_score.user_id = users.id')
            ->selectMore('sum(users_score.score) score')
            ->groupBy('users.id');
    }

    public function getPageLink() {
        return sprintf('<a href="user.php?id=%d">%s</a>', $this->id, $this->name);
    }
}

$usersObject = new Users();
// pre($usersObject,1);

$user = $usersObject->find(1);
pre($user);
// pre($user->getPageLink());
// prd($user->isFound());

// $users = $usersObject->findAll();
// $users = $usersObject->findAll([1,2,3]);
// $users = $usersObject->findAll('users.id in(?)', [[1,2,3]]);
// $users = $usersObject->findAll('users.id in(?,?,?)', [1,2,3]);
// pre($users);
// foreach ($users as $user) {
    // pre($user->name);
// }
// $users = $usersObject->findAll([111111111,222222222,33333333]);
// prd($users->isFound());

// insert
// $user = $usersObject->entity();
// $user->name = 'Deli';
// $user->old = rand(100,500);
// prd($user->save());
// pre($user);

// update
// $user = $usersObject->entity();
// $user->id = 933;
// $user->name = 'Veli';
// $user->old = 55;
// prd($usersObject->save($user));
// pre($user);

// update exists
// $user = $usersObject->find(933);
// $user->old = 100;
// pre($user->save());

// remove
// $result = $usersObject->remove(933);
// $result = $usersObject->remove([931,925,926]);
// prd($result);

// remove exists
// $user = $usersObject->find(937);
// pre($user);
// pre($user->remove());
