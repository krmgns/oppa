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

namespace Oppa\ActiveRecord;

use Oppa\Database;
use Oppa\Query\Result\Result;
use Oppa\Query\Builder as QueryBuilder;
use Oppa\Exception\InvalidValueException;

/**
 * @package    Oppa
 * @subpackage Oppa\ActiveRecord
 * @object     Oppa\ActiveRecord\ActiveRecord
 * @author     Kerem Güneş <k-gun@mail.com>
 */
abstract class ActiveRecord
{
    /**
     * Info.
     * @var array
     */
    private static $info = [];

    /**
     * Database.
     * @var Oppa\Database
     */
    private $db;

    /**
     * Table.
     * @var string
     */
    protected $table;

    /**
     * Table primary.
     * @var string
     */
    protected $tablePrimary;

    /**
     * Bind methods for entity.
     * @var array
     */
    private $bindMethods = [];

    /**
     * Constructor.
     * @param  Oppa\Database $db
     * @throws Oppa\InvalidValueException
     */
    public function __construct(Database $db)
    {
        // check for table, primary key
        if (!isset($this->table, $this->tablePrimary)) {
            throw new InvalidValueException(
                "You need to specify both 'table' and 'tablePrimary' properties!");
        }

        $this->db = $db;
        $this->db->connect();

        // set table info for once
        if (empty(self::$info)) {
            $result = $this->db->getLink()->getAgent()
                ->getAll("SHOW COLUMNS FROM {$this->table}");

            foreach ($result as $result) {
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
     * Entity.
     * @param  array $data
     * @return Oppa\ActiveRecord\Entity
     */
    final public function entity(array $data = []): Entity
    {
        return new Entity($this, $data);
    }

    /**
     * Find.
     * @param  any $param
     * @return Oppa\ActiveRecord\Entity
     * @throws Oppa\InvalidValueException
     */
    final public function find($param): Entity
    {
        $param = [$param];
        if (empty($param)) {
            throw new InvalidValueException("You need to pass a parameter to make a query!");
        }

        $query = new QueryBuilder($this->getDatabase()->getLink());
        $query->setTable($this->table);

        $query->select("{$this->table}.*");

        if (method_exists($this, 'onFind')) {
            $query = $this->onFind($query);
        }

        $query->where("{$this->table}.{$this->tablePrimary} = ?", $param)
            ->limit(1);

        $result = $query->execute()->first();

        return new Entity($this, (array) $result);
    }

    /**
     * Find all.
     * @param  any       $params
     * @param  array     $paramsParams
     * @param  array|int $limit
     * @return Oppa\ActiveRecord\EntityCollection
     */
    final public function findAll($params = null, array $paramsParams = null, $limit = null): EntityCollection
    {
        $query = new QueryBuilder($this->getDatabase()->getLink());
        $query->setTable($this->table);

        $query->select("{$this->table}.*");

        if (method_exists($this, 'onFind')) {
            $query = $this->onFind($query);
        }

        // fetch all rows, oh ohh..
        // e.g: findAll()
        if (empty($params)) {
            // nothing to do..
        }
        // fetch all rows by primary key with given params
        // e.g: findAll([1,2,3])
        elseif (!empty($params) && empty($paramsParams)) {
            $query->where("{$this->table}.{$this->tablePrimary} IN(?)", [$params]);
        }
        // fetch all rows with given params and paramsParams
        // e.g: findAll('id IN (?)', [[1,2,3]])
        // e.g: findAll('id IN (?,?,?)', [1,2,3])
        elseif (!empty($params) && !empty($paramsParams)) {
            // now, it is user's responsibility to append table(s) before field(s)
            $query->where($params, $paramsParams);
        }

        @list($limitStart, $limitStop) = (array) $limit;
        if ($limitStart !== null) {
            $query->limit((int) $limitStart, $limitStop);
        }

        $result = $query->execute();

        $entityCollection = new EntityCollection();
        foreach ($result as $result) {
            $entityCollection->add($this, (array) $result);
        }

        return $entityCollection;
    }

    /**
     * Save.
     * @param  Oppa\ActiveRecord\Entity $entity
     * @return any      On insert: last insert id.
     * @return int|null On update: affected rows.
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

        $agent = $this->db->getLink()->getAgent();

        $return = null;

        // insert action
        if (!isset($entity->{$this->tablePrimary})) {
                      // set primary value
            $return = ($entity->{$this->tablePrimary} = $agent->insert($this->table, $data));
        }
        // update action
        elseif (isset($data[$this->tablePrimary])) {
            $return = $agent->update($this->table, $data,
                "{$this->tablePrimary} = ?", [$data[$this->tablePrimary]]);
        }

        if (method_exists($this, 'onSave')) {
            $this->onSave();
        }

        return $return;
    }

    /**
     * Remove.
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

        $return = $this->db->getLink()->getAgent()
            ->delete($this->table, "{$this->tablePrimary} IN(?)", $params);

        if (method_exists($this, 'onDelete')) {
            $this->onDelete();
        }

        return $return;
    }

    /**
     * Count.
     * @param  string $params
     * @param  array  $paramsParams
     * @return int
     */
    final public function count(string $params = null, array $paramsParams = null): int
    {
        $query = new QueryBuilder($this->getDatabase()->getLink());
        $query->setTable($this->table);

        if (!empty($params) && !empty($paramsParams)) {
            $query->where($params, $paramsParams);
        }

        return $query->count();
    }

    /**
     * Get table.
     * @return string
     */
    final public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get table primary.
     * @return string
     */
    final public function getTablePrimary(): string
    {
        return $this->tablePrimary;
    }

    /**
     * Get bind methods.
     * @return array
     */
    final public function getBindMethods(): array
    {
        return $this->bindMethods;
    }

    /**
     * Set database.
     * @param  Oppa\Database $db
     * @return void
     */
    final public function setDatabase(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get database.
     * @return Oppa\Database
     */
    final public function getDatabase(): Database
    {
        return $this->db;
    }
}
