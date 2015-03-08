Not ready yet, will be documented more..<hr>

**Autoloading / Using Libraries**

```php
$autoload = require('path/to/Oppa/Autoload.php');
$autoload->register();

use \Oppa\Database;
use \Oppa\Configuration;
```

**Simple Usage**

```php
$cfg = [
    'agent'    => 'mysqli',
    'database' => [
        'host'     => 'localhost',  'name'     => 'test',
        'username' => 'test',       'password' => '********',
        'charset'  => 'utf8',       'timezone' => '+00:00',
    ]
];

$db = Database\Factory::build(new Configuration($cfg));
$db->connect();

$agent = $db->getConnection()->getAgent();
$agent->query("delete from `users` where `id` = ?", [123]);
prd($agent->rowsAffected());
```

**Simple ORM**

```php
// set connected database
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
pre($user);

// check user found?
if ($user->isFound()) {
    pre($user->name);
}

// insert a user
$user = $usersObject->entity();
$user->name = 'Veli';
$user->old  = 40;
prd($user->save());
// or
$user = $usersObject->save($user);
// here we see "id" will be filled with last insert id
pre($user);

// update a user "id=1"
$user = $usersObject->entity();
$user->id   = 1;
$user->name = 'Veli';
$user->old  = 55;
prd($usersObject->save($user));

// update a user that already exists "id=1"
$user = $usersObject->find(1);
$user->old = 100;
prd($user->save());

// remove a user "id=1"
$result = $usersObject->remove(1);
prd($result);

// remove a user that already exists "id=1"
$user = $usersObject->find(1);
prd($user->remove());

// remove users "id=1,2,3"
$result = $usersObject->remove([1,2,3]);
prd($result);
```
