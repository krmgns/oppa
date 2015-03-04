<?php
/**
 * Copyright (c) 2015 Kerem Gunes
 *    <http://qeremy.com>
 *
 * GNU General Public License v3.0
 *    <http://www.gnu.org/licenses/gpl-3.0.txt>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Oppa\Orm;

use \Oppa\Database;
use \Oppa\Exception\Orm as Exception;

/**
 * @package    Oppa
 * @subpackage Oppa\Orm
 * @object     Oppa\Orm\Orm
 * @uses       Oppa\Database, Oppa\Exception\Orm
 * @extends    Oppa\Orm\Relation
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */

class Orm
    extends Relation
{
    /**
     * Database object.
     * @var Oppa\Database
     */
    private static $database;

    /**
     * Target entity table.
     * @var string
     */
    protected $table;

    /**
     * Target entity table primary key.
     * @var string
     */
    protected $primaryKey;

    /**
     * Select fields that will mapped in entity.
     * @var array
     */
    protected $selectFields = ['*'];

    /**
     * Relation map
     * @var array
     */
    protected $relations = [];

    /**
     * Binding methods for each entity.
     *
     * @var array
     */
    private $bindingMethods = [];

    /**
     * Create a fresh Orm object.
     *
     * @throws Oppa\Exception\Orm\ArgumentException
     */
    public function __construct() {
        // check for valid database object
        if (!self::$database instanceof Database) {
            throw new Exception\ArgumentException(
                "You need to specify a valid database object");
        }

        // check for table, primary key
        if (!isset($this->table, $this->primaryKey)) {
            throw new Exception\ArgumentException(
                "You need to specify both `table` and `primaryKey` property");
        }

        // prepare once select fields
        if (is_array($this->selectFields)) {
            foreach ($this->selectFields as &$field) {
                if ($field != '*') {
                    $field = $agent->escapeIdentifier($field);
                }
            }
        }

        // methods to bind to the entities
        $className = get_class($this);
        $reflection = new \ReflectionClass($className);
        foreach ($reflection->getMethods() as $method) {
            if ($method->class == $className) {
                $methodName = strtolower($method->name);
                $this->bindingMethods[$methodName] =
                    $reflection->getMethod($methodName)->getClosure($this);
            }
        }
    }

    /**
     * Create a fresh Entity object.
     *
     * @param  array $data
     * @return Oppa\Orm\Entity
     */
    final public function entity(array $data = []) {
        return new Entity($data, $this->bindingMethods);
    }

    /**
     * Find an object in target table an map it in entity collection.
     *
     * @param  mixed         $param
     * @param  callable|null $filter @notimplemented
     * @throws Oppa\Exception\Orm\ArgumentException
     * @return Oppa\Orm\Entity
     */
    final public function find($param, callable $filter = null) {
        $param = [$param];
        if (empty($param)) {
            throw new Exception\ArgumentException(
                "You need to pass a parameter to make a query!");
        }

        $query = $this->generateJoinQuery();

        // fetch one
        $result = self::$database->getConnection()->getAgent()
            ->select($this->getTable(), [$this->getSelectFields()], "{$this->getPrimaryKey()} = ?", $param, 1);
        $result = isset($result[0]) ? $result[0] : null;

        return new Entity((array) $result, $this->bindingMethods);
    }

    /**
     * Find objects in target table an map it in entity collection.
     *
     * @param  mixed         $query
     * @param  mixed         $param
     * @param  callable|null $filter @notimplemented
     * @return Oppa\Orm\EntityCollection
     */
    final public function findAll($query = null, array $params = null, callable $filter = null) {
        // fetch all rows, oh ohh..
        // e.g: findAll()
        if (empty($query)) {
            $result = self::$database->getConnection()->getAgent()
                ->select($this->getTable(), [$this->getSelectFields()]);
        }
        // fetch all rows by primary key with given params
        // e.g: findAll([1,2,3])
        elseif (!empty($query) && empty($params)) {
            $result = self::$database->getConnection()->getAgent()
                ->select($this->getTable(), [$this->getSelectFields()], "{$this->getPrimaryKey()} IN(?)", [$query]);
        }
        // fetch all rows with given query and params
        // e.g: findAll('id IN (?)', [[1,2,3]])
        // e.g: findAll('id IN (?,?,?)', [1,2,3])
        elseif (!empty($query) && !empty($params)) {
            $result = self::$database->getConnection()->getAgent()
                ->select($this->getTable(), [$this->getSelectFields()], $query, $params);
        }

        $entityCollection = new EntityCollection();
        foreach ($result as $result) {
            $entityCollection->add((array) $result, $this->bindingMethods);
        }

        return $entityCollection;
    }

    /**
     * Save entity into target table.s
     *
     * @param  Oppa\Orm\Entity $entity
     * @throws Oppa\Exception\Orm\ErrorException
     * @return mixed
     *   - oninsert: last insert id
     *   - onupdate: affected rows
     */
    final public function save(Entity $entity) {
        $data = $entity->toArray();
        if (empty($data)) {
            throw new Exception\ErrorException(
                'There is no data ehough on entity for save action!');
        }

        $agent = self::$database->getConnection()->getAgent();
        // insert action
        if (!isset($entity->{$this->primaryKey})) {
            return $entity->{$this->primaryKey} = $agent->insert($this->table, $data);
        }
        // update action
        return $agent->update($this->table, $data, "{$this->getPrimaryKey()} = ?", [$data[$this->primaryKey]]);
    }

    /**
     * Remove an entity from target table.
     *
     * @param  mixed $params
     * @throws Oppa\Exception\Orm\ArgumentException
     * @return integer
     */
    final public function remove($params) {
        $params = [$params];
        if (empty($params)) {
            throw new Exception\ArgumentException(
                'You need to pass a parameter to make a query!');
        }

        return self::$database->getConnection()->getAgent()
            ->delete($this->getTable(), "{$this->getPrimaryKey()} IN(?)", $params);
    }

    /**
     * Get entity table.
     *
     * @return string
     */
    final public function getTable($escape = true) {
        return !$escape ? $this->table
            : self::$database->getConnection()->getAgent()->escapeIdentifier($this->table);
    }

    /**
     * Get entity table's primary key.
     *
     * @return string
     */
    final public function getPrimaryKey($escape = true) {
        return !$escape ? $this->primaryKey
            : self::$database->getConnection()->getAgent()->escapeIdentifier($this->primaryKey);
    }

    /**
     * Get entity table's select fields.
     *
     * @return string
     */
    final public function getSelectFields() {
        return empty($this->selectFields) ? '*' :
            (is_array($this->selectFields)
                ? join(',', $this->selectFields) : '*');
    }

    /**
     * Set database object.
     *
     * @param  Oppa\Database $database
     * @return void
     */
    final public static function setDatabase(Database $database) {
        self::$database = $database;
    }

    /**
     * Get database object.
     *
     * @return Oppa\Database
     */
    final public static function getDatabase() {
        return self::$database;
    }
}
