<?php
include('_inc.php');

use Oppa\Database;

// set a worker class
class Db
{
    private static $instance;
    private static $cfg = [
        'agent'    => 'mysql',
        'database' => [
            'host'     => 'localhost',  'name'     => 'test',
            'username' => 'test',       'password' => '********',
            'charset'  => 'utf8',       'timezone' => '+00:00',
        ]
    ];
    private $db;

    private function __clone() {}
    private function __construct() {}

    public static function init() {
        if (self::$instance == null) {
            self::$instance = new self();
            self::$instance->db =
                new Database(self::$cfg);
            self::$instance->db->connect();
        }
        return self::$instance;
    }

    public function query($sql, array $params = null) {
        return $this->db->getLink()->getAgent()->query($sql, $params);
    }
}

// get database instance
$db = Db::init();

// make a regular query
$users = $db->query("select * from `users` limit 3");
foreach ($users as $user) {
    print $user->name;
}
