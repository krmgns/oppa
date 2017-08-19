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

use Oppa\Query\Result\ResultInterface;

/**
 * @package Oppa
 * @object  Oppa\Agent\AgentInterface
 * @author  Kerem Güneş <k-gun@mail.com>
 */
interface AgentInterface
{
    /**
     * Connect.
     * @return void
     */
    public function connect(): void;

    /**
     * Disconnect.
     * @return void
     */
    public function disconnect(): void;

    /**
     * Is connected.
     * @return bool
     */
    public function isConnected(): bool;

    /**
     * Query.
     * @param  string $query
     * @param  array  $queryParams
     * @return Oppa\Query\Result\ResultInterface
     */
    public function query(string $query, array $queryParams = null): ResultInterface;

    /**
     * Select.
     * @param  string       $table
     * @param  string|array $fields
     * @param  string       $where
     * @param  array        $whereParams
     * @param  int|string   $fetchType
     * @return any
     */
    public function select(string $table, $fields = null, string $where = null,
        array $whereParams = null, $fetchType = null);

    /**
     * Select all.
     * @param  string       $table
     * @param  string|array $fields
     * @param  string       $where
     * @param  array        $whereParams
     * @param  int|array    $limit
     * @param  int|string   $fetchType
     * @return any
     */
    public function selectAll(string $table, $fields = null, string $where = null,
        array $whereParams = null, $limit = null, $fetchType = null);

    /**
     * Insert.
     * @param  string $table
     * @param  array  $data
     * @return ?int
     */
    public function insert(string $table, array $data): ?int;

    /**
     * Insert all.
     * @param  string $table
     * @param  array  $data
     * @return ?array
     */
    public function insertAll(string $table, array $data): ?array;

    /**
     * Update.
     * @param  string    $table
     * @param  array     $data
     * @param  string    $where
     * @param  array     $whereParams
     * @param  int|array $limit
     * @return int
     */
    public function update(string $table, array $data, string $where = null,
        array $whereParams = null, $limit = null): int;

    /**
     * Delete.
     * @param  string    $table
     * @param  string    $where
     * @param  array     $whereParams
     * @param  int|array $limit
     * @return int
     */
    public function delete(string $table, string $where = null,
        array $whereParams = null, $limit = null): int;

    /**
     * Count.
     * @param  ?string $table
     * @param  string  $query
     * @param  array   $queryParams
     * @return ?int
     */
    public function count(?string $table, string $query = null, array $queryParams = null): ?int;

    /**
     * Prepare.
     * @param  string $input
     * @param  array  $inputParams
     * @return string
     */
    public function prepare(string $input, array $inputParams = null): string;

    /**
     * Escape.
     * @param  any    $input
     * @param  string $type
     * @return any
     */
    public function escape($input, string $type = null);

    /**
     * Escape string.
     * @param  string $input
     * @param  bool   $quote
     * @return string
     */
    public function escapeString(string $input, bool $quote = true): string;

    /**
     * Escape identifier.
     * @param  string|array $input
     * @return string
     */
    public function escapeIdentifier($input): string;

    /**
     * Where.
     * @param  string $where
     * @param  array  $whereParams
     * @return ?string
     */
    public function where(string $where = null, array $whereParams = null): ?string;

    /**
     * Limit.
     * @param  int|array $limit
     * @return ?string
     */
    public function limit($limit): ?string;
}
