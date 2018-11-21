<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace Oppa\ActiveRecord;

use Oppa\Database;
use Oppa\Query\Result\Result;
use Oppa\Query\Builder as QueryBuilder;
use Oppa\Exception\InvalidValueException;

/**
 * @package Oppa
 * @object  Oppa\ActiveRecord\ActiveRecord
 * @author  Kerem Güneş <k-gun@mail.com>
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
            throw new InvalidValueException("You need to specify both 'table' and 'tablePrimary' properties!");
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
    public final function entity(array $data = []): Entity
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
    public final function find($param): Entity
    {
        if ($param === null || $param === '') {
            throw new InvalidValueException('You need to pass a parameter for select action!');
        }

        $queryBuilder = new QueryBuilder($this->db->getLink());
        $queryBuilder->setTable($this->table);

        $queryBuilder->select("{$this->table}.*");

        if (method_exists($this, 'onFind')) {
            $queryBuilder = $this->onFind($queryBuilder);
            if (!$queryBuilder || !($queryBuilder instanceof QueryBuilder)) {
                throw new InvalidValueException('You should return query builder back from onFind()!');
            }
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
     * @param  array     $queryParams
     * @param  array|int $limit
     * @return Oppa\ActiveRecord\EntityCollection
     */
    public final function findAll($query = null, array $queryParams = null, $limit = null): EntityCollection
    {
        $queryBuilder = new QueryBuilder($this->db->getLink());
        $queryBuilder->setTable($this->table);

        $queryBuilder->select("{$this->table}.*");

        if (method_exists($this, 'onFind')) {
            $queryBuilder = $this->onFind($queryBuilder);
            if (!$queryBuilder || !($queryBuilder instanceof QueryBuilder)) {
                throw new InvalidValueException('You should return query builder back from onFind()!');
            }
        }

        $isEmptyQuery = empty($query);
        $isEmptyQueryParams = empty($queryParams);

        // fetch all rows, oh ohh..
        // e.g: findAll()
        if ($isEmptyQuery) {
            // nothing to do..
        }
        // fetch all rows by primary key with given params
        // e.g: findAll([1,2,3])
        elseif (!$isEmptyQuery && $isEmptyQueryParams) {
            $queryBuilder->where("{$this->table}.{$this->tablePrimary} IN(?)", [$query]);
        }
        // fetch all rows with given params and params
        // e.g: findAll('id IN (?)', [[1,2,3]])
        // e.g: findAll('id IN (?,?,?)', [1,2,3])
        elseif (!$isEmptyQuery && !$isEmptyQueryParams) {
            // now, it is user's responsibility to append table(s) before field(s)
            $queryBuilder->where($query, $queryParams);
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
    public final function save(Entity $entity): ?int
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
    public final function remove(Entity $entity): int
    {
        $primaryValue = $entity->getPrimaryValue();
        if ($primaryValue === null) {
            throw new InvalidValueException('Primary value not found on entity for delete action!');
        }

        return $this->removeAll($primaryValue);
    }

    /**
     * Remove all.
     * @param  any $whereParams
     * @return int
     * @throws Oppa\Exception\InvalidValueException
     */
    public final function removeAll($whereParams): int
    {
        $whereParams = [$whereParams];
        if ($whereParams[0] === null || $whereParams[0] === '') {
            throw new InvalidValueException('You need to pass a parameter for delete action!');
        }

        $return = $this->db->getLink()->getAgent()
            ->deleteAll($this->table, "{$this->tablePrimary} IN(?)", $whereParams);

        if (method_exists($this, 'onRemove')) {
            $this->onRemove($return);
        }

        return $return;
    }

    /**
     * Count.
     * @param  string $query
     * @param  array  $queryParams
     * @return ?int
     */
    public final function count(string $query = null, array $queryParams = null): ?int
    {
        $queryBuilder = new QueryBuilder($this->db->getLink());
        $queryBuilder->setTable($this->table);

        if ($query || $queryParams) {
            $queryBuilder->where($query, $queryParams);
        }

        return $queryBuilder->count();
    }

    /**
     * Get table.
     * @return string
     */
    public final function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get table primary.
     * @return string
     */
    public final function getTablePrimary(): string
    {
        return $this->tablePrimary;
    }

    /**
     * Get table info.
     * @return array
     */
    public final function getTableInfo(): array
    {
        return self::$tableInfo;
    }

    /**
     * Set database.
     * @param  Oppa\Database $db
     * @return void
     */
    public final function setDatabase(Database $db): void
    {
        $this->db = $db;
    }

    /**
     * Get database.
     * @return Oppa\Database
     */
    public final function getDatabase(): Database
    {
        return $this->db;
    }
}
