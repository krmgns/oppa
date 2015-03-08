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
use \Oppa\Database\Query\Builder as QueryBuilder;

/**
 * @package    Oppa
 * @subpackage Oppa\Orm
 * @object     Oppa\Orm\Orm
 * @uses       Oppa\Database, Oppa\Exception\Orm, Oppa\Database\Query\Builder
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
     * Table info.
     * @var array
     */
    private static $info = [];

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

        // set table info for once
        if (empty(self::$info)) {
            $result = self::$database->getConnection()->getAgent()
                ->getAll("SHOW COLUMNS FROM {$this->table}", null, 'array_assoc');

            // will be filled more if needed
            foreach ($result as $result) {
                self::$info[$result['Field']] = [];
            }

            // set field names as shorcut
            self::$info['$fields'] = array_keys(self::$info);
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
        return new Entity($this, $data);
    }

    /**
     * Find an object in target table.
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

        // start query building
        $query = new QueryBuilder($this->getDatabase()->getConnection());
        $query->setTable($this->table);

        // add parent select fields
        $query->select($this->getSelectFields());

        // add more statement for select/where
        if (isset($this->relations['select'])) {
            $query = $this->addSelect($query);
            $query->where("{$this->table}.{$this->primaryKey} = ?", $param);
        } else {
            $query->where("{$this->primaryKey} = ?", $param);
        }

        // add limit
        $query->limit(1);

        // get result
        $result = $query->execute()->first();

        return new Entity($this, (array) $result);
    }

    /**
     * Find objects in target table an map it in entity collection.
     *
     * @param  mixed         $query
     * @param  mixed         $param
     * @param  callable|null $filter @notimplemented
     * @return Oppa\Orm\EntityCollection
     */
    final public function findAll($params = null, array $paramsParams = null, callable $filter = null) {
        // start query building
        $query = new QueryBuilder($this->getDatabase()->getConnection());
        $query->setTable($this->table);

        // add parent select fields
        $query->select($this->getSelectFields());

        // add more statement for select/where
        if (isset($this->relations['select'])) {
            $query = $this->addSelect($query);
        }

        // fetch all rows, oh ohh..
        // e.g: findAll()
        if (empty($params)) {
            // nothing to do..
        }
        // fetch all rows by primary key with given params
        // e.g: findAll([1,2,3])
        elseif (!empty($params) && empty($paramsParams)) {
            !isset($this->relations['select'])
                ? $query->where("{$this->primaryKey} IN(?)", [$params])
                : $query->where("{$this->table}.{$this->primaryKey} IN(?)", [$params]);
        }
        // fetch all rows with given params and paramsParams
        // e.g: findAll('id IN (?)', [[1,2,3]])
        // e.g: findAll('id IN (?,?,?)', [1,2,3])
        elseif (!empty($params) && !empty($paramsParams)) {
            // now, it is user's responsibility to append table(s) before field(s)
            $query->where($params, $paramsParams);
        }

        // get results
        $result = $query->execute();

        // create entity collection
        $entityCollection = new EntityCollection();
        foreach ($result as $result) {
            $entityCollection->add($this, (array) $result);
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

        // use only owned fields
        $data = array_intersect_key($data, array_flip(self::$info['$fields']));

        // get worker agent
        $agent = self::$database->getConnection()->getAgent();

        // insert action
        if (!isset($entity->{$this->primaryKey})) {
            return $entity->{$this->primaryKey} = $agent->insert($this->table, $data);
        }
        // update action
        return $agent->update($this->table, $data,
            "{$this->primaryKey} = ?", [$data[$this->primaryKey]]);
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

        // get worker agent
        $agent = self::$database->getConnection()->getAgent();

        // remove data
        $result = $agent->delete($this->table, "{$this->primaryKey} IN(?)", $params);

        // remove related child(s) data
        if ($result && isset($this->relations['delete'])) {
            foreach ((array) $this->relations['delete'] as $delete) {
                if (isset($delete['table'], $delete['foreign_key'])) {
                    $agent->delete($delete['table'], "{$delete['foreign_key']} IN(?)", $params);
                }
            }
        }

        return $result;
    }

    /**
     * Get entity table.
     *
     * @return string
     */
    final public function getTable() {
        return $this->table;
    }

    /**
     * Get entity table's primary key.
     *
     * @return string
     */
    final public function getPrimaryKey() {
        return $this->primaryKey;
    }

    /**
     * Get entity table's select fields.
     *
     * @return string
     */
    final public function getSelectFields() {
        $fields = '*';
        if (is_array($this->selectFields)) {
            $fields = join(', ', $this->selectFields);
        }

        return $fields;
    }

    /**
     * Get binding methods.
     *
     * @return array
     */
    final public function getBindingMethods() {
        return $this->bindingMethods;
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
