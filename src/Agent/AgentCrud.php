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

namespace Oppa\Agent;

use Oppa\Config;
use Oppa\Batch\BatchInterface;
use Oppa\Query\Result\ResultInterface;
use Oppa\Exception\{Error, InvalidKeyException};

/**
 * @package Oppa
 * @object  Oppa\Agent\AgentCrud
 * @author  Kerem Güneş <k-gun@mail.com>
 */
abstract class AgentCrud
{
    /**
     * @inheritDoc Oppa\Agent\AgentInterface
     */
    public final function select(string $table, $fields = null, string $where = null, array $whereParams = null,
        $fetchType = null)
    {
        if ($fields == null) {
            $fields = '*';
        }

        return $this->query(sprintf('SELECT %s FROM %s %s LIMIT 1',
            $this->escapeIdentifier($fields),
            $this->escapeIdentifier($table),
            $this->where($where, $whereParams)
        ), null, null, $fetchType)->itemFirst();
    }

    /**
     * @inheritDoc Oppa\Agent\AgentInterface
     */
    public final function selectAll(string $table, $fields = null, string $where = null, array $whereParams = null,
        $limit = null, $fetchType = null)
    {
        if ($fields == null) {
            $fields = '*';
        }

        return $this->query(sprintf('SELECT %s FROM %s %s %s',
            $this->escapeIdentifier($fields),
            $this->escapeIdentifier($table),
            $this->where($where, $whereParams),
            $this->limit($limit)
        ), null, null, $fetchType)->getData();
    }

    /**
     * Insert.
     * @param  string $table
     * @param  array  $data
     * @return ?int
     */
    public final function insert(string $table, array $data): ?int
    {
        // simply check is not assoc to prepare multi-insert
        if (!isset($data[0])) {
            $data = [$data];
        }

        $keys = array_keys(current($data));
        $values = [];
        foreach ($data as $dat) {
            $values[] = '('. $this->escape(array_values($dat)) .')';
        }

        return $this->query(sprintf(
            'INSERT INTO %s (%s) VALUES %s',
                $this->escapeIdentifier($table),
                    $this->escapeIdentifier($keys),
                        join(',', $values)
        ))->getId();
    }

    /**
     * Update.
     * @param  string    $table
     * @param  array     $data
     * @param  string    $where
     * @param  array     $whereParams
     * @param  int|array $limit
     * @return int
     */
    public final function update(string $table, array $data, string $where = null, array $whereParams = null,
        $limit = null): int
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = sprintf('%s = %s',
                $this->escapeIdentifier($key), $this->escape($value));
        }

        return $this->query(sprintf(
            'UPDATE %s SET %s %s %s',
                $this->escapeIdentifier($table),
                    join(', ', $set),
                        $this->where($where, $whereParams),
                            $this->limit($limit)
        ))->getRowsAffected();
    }

    /**
     * Delete.
     * @param  string    $table
     * @param  string    $where
     * @param  array     $whereParams
     * @param  int|array $limit
     * @return int
     */
    public final function delete(string $table, string $where = null, array $whereParams = null, $limit = null): int
    {
        return $this->query(sprintf(
            'DELETE FROM %s %s %s',
                $this->escapeIdentifier($table),
                    $this->where($where, $whereParams),
                        $this->limit($limit)
        ))->getRowsAffected();
    }

    /**
     * Get.
     * @param  string $query
     * @param  array  $queryParams
     * @param  string $fetchClass
     * @return object|array|null
     * @throws Oppa\Exception\{InvalidQueryException, InvalidResourceException, QueryException}
     */
    public final function get(string $query, array $queryParams = null, string $fetchClass = null)
    {
        return $this->query($query, $queryParams, 1, $fetchClass)->getDataItem(0);
    }

    /**
     * Get all.
     * @param  string $query
     * @param  array  $queryParams
     * @param  string $fetchClass
     * @return array
     * @throws Oppa\Exception\{InvalidQueryException, InvalidResourceException, QueryException}
     */
    public final function getAll(string $query, array $queryParams = null, string $fetchClass = null): array
    {
        return $this->query($query, $queryParams, null, $fetchClass)->getData();
    }
}
