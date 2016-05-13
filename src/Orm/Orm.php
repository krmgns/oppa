<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *    <k-gun@mail.com>
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
declare(strict_types=1);

namespace Oppa\Orm;

use Oppa\Database;
use Oppa\Query\Result\Result;
use Oppa\Query\Builder as QueryBuilder;
use Oppa\Exception\InvalidValueException;

/**
 * @package    Oppa
 * @subpackage Oppa\Orm
 * @object     Oppa\Orm\Orm
 * @author     Kerem Güneş <k-gun@mail.com>
 */
class Orm extends Relation
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
     * Relation map.
     * @var array
     */
    protected $relations = [];

    /**
     * Bind methods for entity.
     * @var array
     */
    private $bindMethods = [];

    /**
     * Constructor.
     * @throws Oppa\InvalidValueException
     */
    public function __construct()
    {
        // check for valid database object
        if (!self::$database instanceof Database) {
            throw new InvalidValueException("You need to specify a valid database object!");
        }

        // check for table, primary key
        if (!isset($this->table, $this->primaryKey)) {
            throw new InvalidValueException("You need to specify both 'table' and 'primaryKey' properties!");
        }

        // set table info for once
        if (empty(self::$info)) {
            $results = self::$database->getConnection()->getAgent()
                ->getAll("SHOW COLUMNS FROM {$this->table}");

            // will be filled more if needed
            foreach ($results as $result) {
                self::$info[$result->Field] = [];
            }

            // set field names as shorcut
            self::$info['@fields'] = array_keys(self::$info);
        }

        // methods to bind to the entities
        $className = get_class($this);
        $reflection = new \ReflectionClass($className);
        foreach ($reflection->getMethods() as $method) {
            if ($method->class == $className) {
                $methodName = strtolower($method->name);
                $methodPrefix = substr($methodName, 0, 2);
                if ($methodPrefix == '__' || $methodPrefix == 'on') {
                    continue;
                }
                $this->bindMethods[$methodName] = $reflection->getMethod($methodName)->getClosure($this);
            }
        }
    }

    /**
     * Create a fresh Entity object.
     * @param  array $data
     * @return Oppa\Orm\Entity
     */
    final public function entity(array $data = []): Entity
    {
        return new Entity($this, $data);
    }

    /**
     * Find an object in target table.
     * @param  any $param
     * @return Oppa\Orm\Entity
     * @throws Oppa\InvalidValueException
     */
    final public function find($param): Entity
    {
        $param = [$param];
        if (empty($param)) {
            throw new InvalidValueException("You need to pass a parameter to make a query!");
        }

        // start query building
        $query = new QueryBuilder($this->getDatabase()->getConnection());
        $query->setTable($this->table);

        // add parent select fields
        $query->select("{$this->table}.*");

        // add more statement for select/where
        if (isset($this->relations['select'])) {
            $query = $this->addSelect($query);
        }
        $query->where("{$this->table}.{$this->primaryKey} = ?", $param);

        // add limit
        $query->limit(1);

        // get result
        $result = $query->execute()->first();

        return new Entity($this, (array) $result);
    }

    /**
     * Find objects in target table an map it in entity collection.
     * @param  any   $params
     * @param  array $paramsParams
     * @return Oppa\Orm\EntityCollection
     */
    final public function findAll($params = null, array $paramsParams = null): EntityCollection
    {
        // start query building
        $query = new QueryBuilder($this->getDatabase()->getConnection());
        $query->setTable($this->table);

        // add parent select fields
        $query->select("{$this->table}.*");

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
     * Save entity into target table.
     * @param  Oppa\Orm\Entity $entity
     * @return any On insert: last insert id.
     * @return int On update: affected rows.
     * @throws Oppa\InvalidValueException
     */
    final public function save(Entity $entity)
    {
        $data = $entity->toArray();
        if (empty($data)) {
            throw new InvalidValueException('There is no data ehough on entity for save action!');
        }

        // use only owned fields
        $data = array_intersect_key($data, array_flip(self::$info['@fields']));

        // get worker agent
        $agent = self::$database->getConnection()->getAgent();

        // insert action
        if (!isset($entity->{$this->primaryKey})) {
            return ($entity->{$this->primaryKey} = $agent->insert($this->table, $data));
        }

        // update action
        return $agent->update($this->table, $data,
            "{$this->primaryKey} = ?", [$data[$this->primaryKey]]);
    }

    /**
     * Remove an entity from target table.
     * @param  any $params
     * @return int
     * @throws Oppa\InvalidValueException
     */
    final public function remove($params): int
    {
        $params = [$params];
        if (empty($params)) {
            throw new InvalidValueException('You need to pass a parameter to make a query!');
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
     * @return string
     */
    final public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get entity table's primary key.
     * @return string
     */
    final public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Get bind (user) methods.
     * @return array
     */
    final public function getBindMethods(): array
    {
        return $this->bindMethods;
    }

    /**
     * Set database object.
     * @param  Oppa\Database $database
     * @return void
     */
    final public static function setDatabase(Database $database)
    {
        self::$database = $database;
    }

    /**
     * Get database object.
     * @return Oppa\Database
     */
    final public static function getDatabase(): Database
    {
        return self::$database;
    }
}
