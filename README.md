Not ready yet, will be documented properly..

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
    'agent' => 'mysqli',
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
print $agent->rowsAffected();
```
