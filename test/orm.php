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
            'left join' => [
                ['table' => 'users_score', 'foreign_key' => 'user_id', 'using' => false,
                    'fields' => ['score']],
                ['table' => 'users_login', 'foreign_key' => 'user_id', 'using' => false,
                    'fields' => ['date_format(from_unixtime(login), "%Y-%m-%d %H:%i:%s") as login']],
            ],
            // 'join' => [
            //     ['table' => 'users_foo', 'foreign_key' => 'user_id', 'using' => true,
            //         'fields' => ['users_foo.aaa', 'sum(x)', 'count(*)', 'xyz']],
            // ],
            'group by' => 'users.id',
        ]
    ];
}

$usersObject = new Users();
// pre($usersObject);

// $user = $usersObject->find(1);
// pre($user);
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
// $user->old = 35;
// pre($user);
// prd($usersObject->save($user));
// pre($user);

// update
// $user = $usersObject->entity();
// $user->id = 933;
// $user->name = 'Veli';
// $user->old = 55;
// pre($user);
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
// $user = $usersObject->find(930);
// pre($user);
// pre($user->remove());
