<?php
include('inc.php');

$autoload = require('./../Oppa/Autoload.php');
$autoload->register();

use \Oppa\Database;
use \Oppa\Configuration;

$cfg = [
    'agent' => 'mysqli',
    'database' => [
        'host' => 'localhost', 'name' => 'test',
        'username' => 'test',  'password' => '********',
    ]
];

$db = Database\Factory::build(new Configuration($cfg));
$db->connect();
// pre($db);

\Oppa\Orm::setDatabase($db);

class Users extends \Oppa\Orm {
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $selectFields = ['id', 'name'];

    // public function getPageLink() {
    //     return sprintf('<a href="user.php?id=%d">%s</a>', $this->id, $this->name);
    // }

    protected $relations = [
        'select' => [
            'join' => [
                ['table' => 'users_score', 'foreign_key' => 'user_id', 'using' => false,
                    'fields' => ['score'], 'field_prefix' => '', 'group_by' => 'users_score.user_id'],
                ['table' => 'users_login', 'foreign_key' => 'user_id', 'using' => false,
                    'fields' => ['login'], 'field_prefix' => 'foo'],
            ],
            // 'left join' => [
            //     ['table' => 'users_foo', 'foreign_key' => 'user_id', 'using' => true,
            //         'fields' => ['users_foo.aaa', 'sum(x)', 'count(*)'], 'field_prefix' => ''],
            // ]
        ]
    ];
}

$usersObject = new Users();
// pre($usersObject);

$user = $usersObject->find(1);
pre($user);
// pre($user->getPageLink());
// prd($user->isFound());

// $users = $usersObject->findAll();
// $users = $usersObject->findAll([1,2,3]);
// $users = $usersObject->findAll('id in(?,?,?)', [1,2,3]);
// pre($users);
// foreach ($users as $user) {
//     pre($user->name);
// }
// $users = $usersObject->findAll([111111111,222222222,33333333]);
// prd($users->isFound());

// $user = $usersObject->entity();
// $user->name = 'Deli';
// $user->old = 35;
// pre($user);
// prd($usersObject->save($user));
// pre($user);

// $user = $usersObject->entity();
// $user->id = 931;
// $user->name = 'Veli';
// $user->old = 45;
// pre($user);
// prd($usersObject->save($user));
// pre($user);

// $result = $usersObject->remove(931);
// $result = $usersObject->remove([931,925,926]);
// prd($result);
