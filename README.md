Oppa, providing an easy interface, aims to simplify database transactions that you tire. Oppa has also an ORM implementation interface that sometimes make the things easier for you.

Oppa secures user inputs sharply, carries results of your queries gently, handles errors smoothly and makes batch transactions/commits carefully for you. Oppa also provides a powerful logging mechanizm to report events that you may wonder about.

You will be enjoying while using it, promise.. :)

Before beginning;

- Set your autoloader properly
- Use PHP >= 5.4
- Handle errors with try/catch blocks
- You can use `test.sql` in test folder
- See `test/inc.php` to know `pre` and `prd` functions if you want

**Autoloading / Using Libraries**

```php
// composer
{"require": {"qeremy/oppa": "dev-master"}}

// manual
$autoload = require('path/to/Oppa/Autoload.php');
$autoload->register();

// and using
use \Oppa\Database;
use \Oppa\Configuration;
```

**Configuration**

```php
$cfg = [
    'agent'    => 'mysqli',
    'database' => [
        'host'     => 'localhost',  'name'     => 'test',
        'username' => 'test',       'password' => '********',
        'charset'  => 'utf8',       'timezone' => '+00:00',
    ]
];
```

**Simple Usage**

```php
$db = Database\Factory::build(new Configuration($cfg));
$db->connect();

$agent = $db->getConnection()->getAgent();
$agent->query('update `users` set `old` = ? where `id` = ?', [30, 1]);
var_dump($agent->rowsAffected());
```

**Simple ORM**

```php
// set orm database that already connected (like above)
\Oppa\Orm::setDatabase($db);

class Users extends \Oppa\Orm {
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $selectFields = ['id', 'name', 'old'];
}

// init orm object
$usersObject = new Users();

// find one that id=1
$user = $usersObject->find(1);
var_dump($user);

// check user found?
if ($user->isFound()) {
    print($user->name);
}

// find all
$users = $usersObject->findAll();
// find many that id=1,2,3
$users = $usersObject->findAll([1,2,3]);
$users = $usersObject->findAll('id in(?)', [[1,2,3]]);
$users = $usersObject->findAll('id in(?,?,?)', [1,2,3]);
var_dump($users);
foreach ($users as $user) {
    print($user->name ."\n");
}
$users = $usersObject->findAll([1111111111,2222222222,3333333333]);
var_dump($users->isFound()); // false

// insert a user
$user = $usersObject->entity();
$user->name = 'Ali';
$user->old  = 40;
var_dump($user->save());
// or
$user = $usersObject->save($user);
// here we see "id" will be filled with last insert id
var_dump($user);

// update a user "id=1"
$user = $usersObject->entity();
$user->id   = 1;
$user->name = 'Ali';
$user->old  = 55;
var_dump($usersObject->save($user));

// update a user that already exists "id=1"
$user = $usersObject->find(1);
$user->old = 100;
var_dump($user->save());

// remove a user "id=1"
$result = $usersObject->remove(1);
var_dump($result);

// remove a user that already exists "id=1"
$user = $usersObject->find(1);
var_dump($user->remove());

// remove users "id=1,2,3"
$result = $usersObject->remove([1,2,3]);
var_dump($result);
```

**Notice**

A detailed wiki comes soon.
