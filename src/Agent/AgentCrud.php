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

namespace Oppa\Agent;

use Oppa\Config;
use Oppa\Batch\BatchInterface;
use Oppa\Query\Result\ResultInterface;
use Oppa\Exception\InvalidValueException;

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
    public final function select(string $table, $fields = null, string $where = null,
        array $whereParams = null, $fetchType = null)
    {
        $return = $this->selectAll($table, $fields, $where, $whereParams, $fetchType, 1);

        return $return[0] ?? null;
    }

    /**
     * @inheritDoc Oppa\Agent\AgentInterface
     */
    public final function selectAll(string $table, $fields = null, string $where = null,
        array $whereParams = null, $fetchType = null, $limit = null): ?array
    {
        if ($fields == null) {
            $fields = '*';
        }

        $query = sprintf('SELECT %s FROM %s %s %s',
            $this->escapeIdentifier($fields),
            $this->escapeIdentifier($table),
            $this->where($where, $whereParams),
            $this->limit($limit)
        );

        $return = $this->query($query, null, null, $fetchType)->getData();

        return $return ? $return : null;
    }

    /**
     * @inheritDoc Oppa\Agent\AgentInterface
     */
    public final function insert(string $table, array $data): ?int
    {
        $return = $this->insertAll($table, [$data]);

        return $return[0] ?? null;
    }

    /**
     * @inheritDoc Oppa\Agent\AgentInterface
     */
    public final function insertAll(string $table, array $data): ?array
    {
        $keys = array_keys((array) @ $data[0]);
        $values = [];
        foreach ($data as $dat) {
            $values[] = '('. $this->escape(array_values((array) $dat)) .')';
        }

        if (empty($keys) || empty($values)) {
            throw new InvalidValueException('Empty keys or/and values given!');
        }

        $query = sprintf('INSERT INTO %s (%s) VALUES %s',
            $this->escapeIdentifier($table),
            $this->escapeIdentifier($keys),
            join(',', $values)
        );

        $return = $this->query($query)->getIds();

        return $return ? $return : null;
    }

    /**
     * @inheritDoc Oppa\Agent\AgentInterface
     */
    public final function update(string $table, array $data, string $where = null,
        array $whereParams = null): int
    {
        return $this->updateAll($table, $data, $where, $whereParams, 1);
    }

    /**
     * @inheritDoc Oppa\Agent\AgentInterface
     */
    public final function updateAll(string $table, array $data, string $where = null,
        array $whereParams = null, int $limit = null): int
    {
        if (empty($data)) {
            throw new InvalidValueException('Empty data given!');
        }

        $set = [];
        foreach ($data as $key => $value) {
            $set[] = sprintf('%s = %s', $this->escapeIdentifier($key), $this->escape($value));
        }

        $query = sprintf('UPDATE %s SET %s %s %s',
            $this->escapeIdentifier($table),
            join(', ', $set),
            $this->where($where, $whereParams),
            $this->limit($limit)
        );

        return $this->query($query)->getRowsAffected();
    }

    /**
     * @inheritDoc Oppa\Agent\AgentInterface
     */
    public final function delete(string $table, string $where = null, array $whereParams = null): int
    {
        return $this->deleteAll($table, $where, $whereParams, 1);
    }

    /**
     * @inheritDoc Oppa\Agent\AgentInterface
     */
    public final function deleteAll(string $table, string $where = null, array $whereParams = null,
        $limit = null): int
    {
        $query = sprintf(
            'DELETE FROM %s %s %s',
            $this->escapeIdentifier($table),
            $this->where($where, $whereParams),
            $this->limit($limit)
        );

        return $this->query($query)->getRowsAffected();
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
