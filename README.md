##Oppa##

Oppa, providing an easy interface, aims to simplify database CRUD operations/transactions that you tire. Oppa has also an ORM implementation interface that sometimes make the things easier for you.

Oppa secures user inputs sharply, carries results of your queries gently, handles errors smoothly, makes batch transactions/commits carefully and profiles your all query processes optionally for you. Oppa also provides a powerful logging mechanizm to report events that you may wonder about.

You will be enjoying while using it, promise.. :)

Before beginning;

- Set your autoloader properly
- Use PHP >= 5.4
- Handle errors with try/catch blocks
- You can use `test.sql` in test folder
- See `test/inc.php` to know `pre` and `prd` functions if you want

You can see wiki pages for more details: https://github.com/qeremy/oppa/wiki

###Autoloading / Using Libraries###

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

###Configuration###

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

###Simple Usage###

```php
$db = Database\Factory::build(new Configuration($cfg));
$db->connect();

$agent = $db->getConnection()->getAgent();
$agent->query('update `users` set `old` = ? where `id` = ?', [30, 1]);
var_dump($agent->rowsAffected());
```

###Holy CRUD Stuffs###

```php
// raw queries
$result = $agent->query('select * from `users`');
if ($result->count())
if ($result->getRowsCount())
    foreach ($result as $user)
        print $user->name;
// or
if ($agent->rowsCount())
    foreach ($agent->getResult() as $user)
    foreach ($agent->getResult()->getData() as $user)
        print $user->name;

// fetch one
$user = $agent->get('select * from `users` where `old` > ?', [50]);
print $user->name;
// fetch all
$users = $agent->getAll('select * from `users` where `old` > ?', [50]);
foreach ($users as $user)
    print $user->name;

// or shorcut methods

// get all users
$result = $agent->select('users', ['*']);
// get all users if old greater than 50
$result = $agent->select('users', ['*'], 'old > ?', [50]);
// get one user
$result = $agent->select('users', ['*'], null, null, 1);

// insert a user
$result = $agent->insert('user', ['name' => 'Ali', 'old' => 30]);
var_dump($result); // int: last_insert_id
// update a user
$result = $agent->update('user', ['old' => 30], 'id = ?', [123]);
var_dump($result); // int: affected_rows
// delete a user
$result = $agent->delete('user', 'id = ?', [123]);
var_dump($result); // int: affected_rows
```

###Query Builder###
```php
// use and init with exists $db
use \Oppa\Database\Query\Builder as QueryBuilder;
$qb = new QueryBuilder($db->getConnection());
// set target table
$qb->setTable('users');

// build query
$qb->select('u.*, us.score, ul.login')
    ->aggregate('sum', 'us.score', 'sum_score')
    ->join('users_score us', 'us.user_id=u.id')
    ->joinLeft('users_login ul', 'ul.user_id=u.id')
    ->where('u.id in(?,?,?)', [1,2,3])
    ->whereBetween('u.old', [30,50])
    ->whereNotNull('ul.login')
    ->groupBy('u.id')
    ->orderBy('old')
    ->having('sum_score <= ?', [30])
    ->limit(0,10)
;
```
Gives the result below.
```sql
SELECT
    u.*
    , us.score
    , ul.login
    , sum(us.score) sum_score
FROM users u
JOIN users_score us ON us.user_id=u.id
LEFT JOIN users_login ul ON ul.user_id=u.id
WHERE u.id in(1,2,3) AND u.old BETWEEN 30 AND 50 AND ul.login IS NOT NULL
GROUP BY u.id
HAVING sum_score <= 30
ORDER BY old
LIMIT 0,10
```


###Batch Actions (aka Transactions)###

```php
$batch = $agent->getBatch();
// set autocommit=0
$batch->lock();
try {
    $batch->queue('insert into `users` values(null,?,?)', ['John', 25]);
    $batch->queue('insert into `users` values(null,?,?)', ['Boby', 35]);
    $batch->queue('insert into `userz` values(null,?,?)', ['Eric', 15]); // boom!
    // commit
    $batch->run();
} catch (\Exception $e) {
    print $e->getMessage();
    // rollback & set autocommit=1
    $batch->cancel();
}
// set autocommit=1
$batch->unlock();

// get insert ids if success
foreach ($batch->getResult() as $result) {
    print $result->getId();
}

// remove query queue and empty result array
$batch->reset();
```

###Simple ORM###

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
    print $user->name;
}

// find all
$users = $usersObject->findAll();
// find many that id=1,2,3
$users = $usersObject->findAll([1,2,3]);
$users = $usersObject->findAll('id in(?)', [[1,2,3]]);
$users = $usersObject->findAll('id in(?,?,?)', [1,2,3]);
var_dump($users);
foreach ($users as $user) {
    print $user->name;
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
$user->name = 'Ali';
$user->old  = 100;
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

See wiki pages for more details: https://github.com/qeremy/oppa/wiki
