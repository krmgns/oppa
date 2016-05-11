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

namespace Oppa\Shablon\Database\Connector\Agent;

/**
 * @package    Oppa
 * @subpackage Oppa\Shablon\Database\Connector\Agent
 * @object     Oppa\Shablon\Database\Connector\Agent\StreamWrapperInterface
 * @author     Kerem Güneş <k-gun@mail.com>
 */
interface StreamWrapperInterface
{
    /**
     * Query.
     * @param string $query
     * @param array  $params
     */
    public function query(string $query, array $params = null);

    /**
     * Get.
     * @param  string $query
     * @param  array  $params
     * @return object|array|null
     */
    public function get(string $query, array $params = null);

    /**
     * Get all.
     * @param string $query
     * @param array  $params
     * @return array
     */
    public function getAll(string $query, array $params = null);

    /**
     * Select.
     * @param  string       $table
     * @param  string|array $fields
     * @param  string       $where
     * @param  array        $params
     * @param  int|array    $limit
     * @param  int          $fetchType
     * @return any
     */
    public function select(string $table, $fields = null, string $where = null,
        array $params = null, $limit = null, int $fetchType = null);

    /**
     * Select one.
     * @param  string       $table
     * @param  string|array $fields
     * @param  string       $where
     * @param  array        $params
     * @param  int          $fetchType
     * @return any
     */
    public function selectOne(string $table, $fields = null, string $where = null,
        array $params = null, int $fetchType = null);

    /**
     * Insert.
     * @param  string $table
     * @param  array  $data
     * @return int|null
     */
    public function insert(string $table, array $data);

    /**
     * Update.
     * @param  string    $table
     * @param  array     $data
     * @param  string    $where
     * @param  array     $params
     * @param  int|array $limit
     * @return int
     */
    public function update(string $table, array $data, string $where = null,
        array $params = null, $limit = null);

    /**
     * Delete.
     * @param  string    $table
     * @param  string    $where
     * @param  array     $params
     * @param  int|array $limit
     * @return int
     */
    public function delete(string $table, string $where = null,
        array $params = null, $limit = null);

    /**
     * Id.
     * @return any
     */
    public function id();

    /**
     * Rows count.
     * @return int
     */
    public function rowsCount();

    /**
     * Rows affected.
     * @return int
     */
    public function rowsAffected();
}
