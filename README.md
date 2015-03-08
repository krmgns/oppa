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
```
