Oppa, providing an easy API support, aims to simplify database transactions that you tire. Oppa has also ORM implementation that sometimes make the things easier for you. Oppa secures user inputs for you, handles errors smoothly and gives a powerful logging mechanizm and makes batch transactions/commits carefully for you.

You will enjoy while using it, promise.. :)

Before beginning;

- Set your autoloader properly
- Use PHP >= 5.4
- Handle errors with try/catch blocks
- See `test/inc.php` to know `pre` and `prd` functions if you wonder.

---

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

// insert a user
$user = $usersObject->entity();
$user->name = 'Veli';
$user->old  = 40;
var_dump($user->save());
// or
$user = $usersObject->save($user);
// here we see "id" will be filled with last insert id
var_dump($user);

// update a user "id=1"
$user = $usersObject->entity();
$user->id   = 1;
$user->name = 'Veli';
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
