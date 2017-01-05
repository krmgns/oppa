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
     * Table info.
     * @var array
     */
    private static $tableInfo = [];

    /**
     * Constructor.
     * @param  Oppa\Database $db
     * @throws Oppa\Exception\InvalidValueException
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
        if (empty(self::$tableInfo)) {
            $result = $this->db->getLink()->getAgent()
                ->get("SELECT * FROM {$this->table} LIMIT 1");

            // set field names as shorcut
            self::$tableInfo['@fields'] = array_keys((array) $result);
        }
    }

    /**
     * Entity.
     * @param  array $data
     * @return Oppa\ActiveRecord\Entity
     */
    final public function entity(array $data = []): Entity
    {
        $entity = new Entity($this, $data);
        if (method_exists($this, 'onEntity')) {
            $this->onEntity($entity);
        }

        return $entity;
    }

    /**
     * Find.
     * @param  any $param
     * @return Oppa\ActiveRecord\Entity
     * @throws Oppa\Exception\InvalidValueException
     */
    final public function find($param): Entity
    {
        if ($param === null || $param === '') {
            throw new InvalidValueException("You need to pass a parameter for select action!");
        }

        $queryBuilder = new QueryBuilder($this->db->getLink());
        $queryBuilder->setTable($this->table);

        $queryBuilder->select("{$this->table}.*");

        if (method_exists($this, 'onFind')) {
            $queryBuilder = $this->onFind($queryBuilder);
        }

        $queryBuilder->where("{$this->table}.{$this->tablePrimary} = ?", [$param])
            ->limit(1);

        $result = $queryBuilder->run()->itemFirst();

        $entity = new Entity($this, (array) $result);
        if (method_exists($this, 'onEntity')) {
            $this->onEntity($entity);
        }

        return $entity;
    }

    /**
     * Find all.
     * @param  any       $query
     * @param  array     $params
     * @param  array|int $limit
     * @return Oppa\ActiveRecord\EntityCollection
     */
    final public function findAll($query = null, array $params = null, $limit = null): EntityCollection
    {
        $queryBuilder = new QueryBuilder($this->db->getLink());
        $queryBuilder->setTable($this->table);

        $queryBuilder->select("{$this->table}.*");

        if (method_exists($this, 'onFind')) {
            $queryBuilder = $this->onFind($queryBuilder);
        }

        $isEmptyQuery = empty($query);
        $isEmptyParams = empty($params);

        // fetch all rows, oh ohh..
        // e.g: findAll()
        if ($isEmptyQuery) {
            // nothing to do..
        }
        // fetch all rows by primary key with given params
        // e.g: findAll([1,2,3])
        elseif (!$isEmptyQuery && $isEmptyParams) {
            $queryBuilder->where("{$this->table}.{$this->tablePrimary} IN(?)", [$query]);
        }
        // fetch all rows with given params and params
        // e.g: findAll('id IN (?)', [[1,2,3]])
        // e.g: findAll('id IN (?,?,?)', [1,2,3])
        elseif (!$isEmptyQuery && !$isEmptyParams) {
            // now, it is user's responsibility to append table(s) before field(s)
            $queryBuilder->where($query, $params);
        }

        @ [$limitStart, $limitStop] = (array) $limit;
        if ($limitStart !== null) {
            $queryBuilder->limit((int) $limitStart, $limitStop);
        }

        $hasOnEntity = method_exists($this, 'onEntity');

        $entityCollection = new EntityCollection();
        foreach ($queryBuilder->run() as $result) {
            $entity = new Entity($this, (array) $result);
            if ($hasOnEntity) {
                $this->onEntity($entity);
            }
            $entityCollection->addEntity($entity);
        }

        return $entityCollection;
    }

    /**
     * Save.
     * @param  Oppa\ActiveRecord\Entity $entity
     * @return ?int On insert: last insert id.
     * @return int  On update: affected rows.
     * @throws Oppa\Exception\InvalidValueException
     */
    final public function save(Entity $entity): ?int
    {
        $data = $entity->toArray();
        if (empty($data)) {
            throw new InvalidValueException('There is no data enough on entity for save action!');
        }

        // use only owned fields
        $data = array_intersect_key($data, array_flip(self::$tableInfo['@fields']));

        $return = null;

        // insert action
        if (!$entity->hasPrimaryValue()) {
            $return = $this->db->getLink()->getAgent()->insert($this->table, $data);
            // set primary value
            $entity->setPrimaryValue($return);
        } else {
            // update action
            $return = $this->db->getLink()->getAgent()->update($this->table, $data,
                "{$this->tablePrimary} = ?", [$entity->getPrimaryValue()]);
        }

        if (method_exists($this, 'onSave')) {
            $this->onSave($return);
        }

        return $return;
    }

    /**
     * Remove.
     * @param  Oppa\ActiveRecord\Entity $entity
     * @return int
     * @throws Oppa\Exception\InvalidValueException
     */
    final public function remove(Entity $entity): int
    {
        $primaryValue = $entity->getPrimaryValue();
        if ($primaryValue === null) {
            throw new InvalidValueException('Primary value not found on entity for delete action!');
        }

        return $this->removeAll($primaryValue);
    }

    /**
     * Remove all.
     * @param  any $params
     * @return int
     * @throws Oppa\Exception\InvalidValueException
     */
    final public function removeAll($params): int
    {
        $params = [$params];
        if ($params[0] === null || $params[0] === '') {
            throw new InvalidValueException('You need to pass a parameter for delete action!');
        }

        $return = $this->db->getLink()->getAgent()
            ->delete($this->table, "{$this->tablePrimary} IN(?)", $params);

        if (method_exists($this, 'onDelete')) {
            $this->onDelete($return);
        }

        return $return;
    }

    /**
     * Count.
     * @param  string $query
     * @param  array  $params
     * @return ?int
     */
    final public function count(string $query = null, array $params = null): ?int
    {
        $queryBuilder = new QueryBuilder($this->db->getLink());
        $queryBuilder->setTable($this->table);

        $isEmptyQuery = empty($query);
        $isEmptyParams = empty($params);

        if (!$isEmptyQuery && !$isEmptyParams) {
            $queryBuilder->where($query, $params);
        } elseif (!$isEmptyQuery && $isEmptyParams) {
            $queryBuilder->where($query);
        }

        return $queryBuilder->count();
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
     * Get table info.
     * @return array
     */
    final public function getTableInfo(): array
    {
        return self::$tableInfo;
    }

    /**
     * Set database.
     * @param  Oppa\Database $db
     * @return void
     */
    final public function setDatabase(Database $db): void
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
