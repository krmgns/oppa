<?php namespace Oppa\Orm;

use \Oppa\Database;
use \Oppa\Helper;
use \Oppa\Exception\Orm as Exception;

class Orm
{
    private static $database;

    protected $table;
    protected $primaryKey;
    protected $selectFields = ['*'];

    public function __construct() {
        if (!isset(self::$database)) {
            throw new Exception\ArgumentException(
                "You need to specify a valid database object");
        }

        if (!isset($this->table, $this->primaryKey)) {
            throw new Exception\ArgumentException(
                "You need to specify both `table` and `primaryKey` property");
        }

        $agent = self::$database->getConnection()->getAgent();
        if (preg_match('~^[\w]+$~', $this->table)) {
            $this->table = $agent->escapeIdentifier($this->table);
        }
        if (preg_match('~^[\w]+$~', $this->primaryKey)) {
            $this->primaryKey = $agent->escapeIdentifier($this->primaryKey);
        }
        if (is_array($this->selectFields)) {
            foreach ($this->selectFields as &$field) {
                if ($field != '*') {
                    $field = $agent->escapeIdentifier($field);
                }
            }
        }
    }

    final public function entity(array $data = []) {
        return new Entity($data);
    }

    final public function find($param, callable $filter = null) {
        $param = [$param];
        if (empty($param)) {
            throw new Exception\ArgumentException(
                "You need to pass a parameter to make a query!");
        }

        $result = self::$database->getConnection()->getAgent()
            ->select($this->getTable(), [$this->getSelectFields()], "{$this->getPrimaryKey()} = ?", $param);

        $entityCollection = new EntityCollection();
        $entityCollection->add(isset($result[0]) ? (array) $result[0] : []);
        return $entityCollection->first();
    }

    final public function findAll($query = null, array $params = null) {
        if (empty($query)) {
            $result = self::$database->getConnection()->getAgent()
                ->select($this->getTable(), [$this->getSelectFields()]);
        } elseif (!empty($query) && empty($params)) {
            $result = self::$database->getConnection()->getAgent()
                ->select($this->getTable(), [$this->getSelectFields()], "{$this->getPrimaryKey()} IN(?)", [$query]);
        } elseif (!empty($query) && !empty($params)) {
            $result = self::$database->getConnection()->getAgent()
                ->select($this->getTable(), [$this->getSelectFields()], $query, $params);
        }

        $entityCollection = new EntityCollection();
        foreach ($result as $result) {
            $entityCollection->add((array) $result);
        }
        return $entityCollection;
    }

    final public function save(Entity $entity) {
        $data = $entity->toArray();
        if (empty($data)) {
            throw new Exception\ErrorException(
                'There is no data ehough on entity for save action!');
        }

        $agent = self::$database->getConnection()->getAgent();

        // trim escapes like "`"
        $primaryKey = substr($this->primaryKey, 1, -1);

        // insert action
        if (!isset($entity->{$primaryKey})) {
            return $entity->{$primaryKey} = $agent->insert($this->table, $data);
        }

        // update action
        return $agent->update($this->table, $data, "`{$primaryKey}` = ?", [$data[$primaryKey]]);
    }

    final public function remove($params) {
        $params = [$params];
        if (empty($params)) {
            throw new Exception\ArgumentException(
                "You need to pass a parameter to make a query!");
        }

        return self::$database->getConnection()->getAgent()
            ->delete($this->getTable(), "{$this->getPrimaryKey()} IN(?)", $params);
    }

    final public function getTable() {
        return $this->table;
    }

    final public function getPrimaryKey() {
        return $this->primaryKey;
    }

    final public function getSelectFields() {
        return empty($this->selectFields) ? '*' :
            (is_array($this->selectFields)
                ? join(',', $this->selectFields) : '*');
    }

    final public static function getDatabase() {
        return self::$database;
    }

    final public static function setDatabase(Database $database) {
        self::$database = $database;
    }
}
