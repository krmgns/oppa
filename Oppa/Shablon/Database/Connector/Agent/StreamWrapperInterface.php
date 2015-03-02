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

namespace Oppa\Shablon\Database\Connector\Agent;

/**
 * @package    Oppa
 * @subpackage Oppa\Shablon\Database\Connector\Agent
 * @object     Oppa\Shablon\Database\Connector\Agent\StreamWrapperInterface
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
interface StreamWrapperInterface
{
    /**
     * Action pattern.
     *
     * @param  string     $query
     * @param  array|null $params
     */
    public function query($query, array $params = null);

    /**
     * Action pattern.
     *
     * @param  string     $query
     * @param  array|null $params
     * @param  integer    $fetchType
     */
    public function get($query, array $params = null, $fetchType = null);

    /**
     * Action pattern.
     *
     * @param  string     $query
     * @param  array|null $params
     * @param  integer    $fetchType
     */
    public function getAll($query, array $params = null, $fetchType = null);

    /**
     * Action pattern.
     *
     * @param  string     $table
     * @param  array      $fields
     * @param  string     $where
     * @param  array|null $params
     * @param  integer    $limit
     * @param  integer    $fetchType
     */
    public function select($table, array $fields, $where = null, array $params = null, $limit = null, $fetchType = null);

    /**
     * Action pattern.
     *
     * @param  string $table
     * @param  array  $data
     */
    public function insert($table, array $data);

    /**
     * Action pattern.
     *
     * @param  string     $table
     * @param  array      $data
     * @param  string     $where
     * @param  array|null $params
     * @param  integer    $limit
     */
    public function update($table, array $data, $where = null, array $params = null, $limit = null);

    /**
     * Action pattern.
     *
     * @param  string     $table
     * @param  string     $where
     * @param  array|null $params
     * @param  integer    $limit
     */
    public function delete($table, $where = null, array $params = null, $limit = null);

    /** Action pattern. */
    public function id(); // uuid, guid, serial, sequence, identity, last_insert_id @WTF!

    /** Action pattern. */
    public function rowsCount();

    /** Action pattern. */
    public function rowsAffected();
}
