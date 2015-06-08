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

namespace Oppa\Database\Query\Result;

use \Oppa\Database\Connector\Agent;
use \Oppa\Exception\Database as Exception;

/**
 * @package    Oppa
 * @subpackage Oppa\Database\Query\Result
 * @object     Oppa\Database\Query\Result\Mysqli
 * @uses       Oppa\Exception\Database
 * @extends    Oppa\Database\Query\Result
 * @version    v1.2
 * @author     Kerem Gunes <qeremy@gmail>
 */
final class Mysqli
    extends \Oppa\Database\Query\Result
{
    /**
     * Create a fresh Mysqli object.
     *
     * @param Oppa\Database\Connector\Agent\Mysqli $agent
     */
    final public function __construct(Agent\Mysqli $agent) {
        $this->agent = $agent;
    }

    /**
     * Free resource.
     *
     * @return void
     */
    final public function free() {
        if ($this->result instanceof \mysqli_result) {
            $this->result->free();
            $this->result = null;
        }
    }

    /**
     * Process result.
     *
     * If query action contains "select", then process returned result.
     * If query action contains "update/delete", etc then process affected result.
     *
     * @param  object/resource $link
     * @param  object/resource $result
     * @param  integer         $limit
     * @param  integer         $fetchType
     * @throws Oppa\Exception\Database\ArgumentException
     * @return self
     */
    final public function process($link, $result, $limit = null, $fetchType = null) {
        // check link
        if (!$link instanceof \mysqli) {
            throw new Exception\ArgumentException(
                'Process link must be instanceof mysqli!');
        }

        $i = 0;
        // if result contains result object
        if ($result instanceof \mysqli_result && $result->num_rows) {
            $this->result = $result;

            if ($limit == null) {
                $limit = PHP_INT_MAX;
            }

            $fetchType = ($fetchType == null)
                ? $this->fetchType
                : $this->detectFetchType($fetchType);

            switch ($fetchType) {
                case self::FETCH_OBJECT:
                    while ($i < $limit && $row = $this->result->fetch_object()) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case self::FETCH_ARRAY_ASSOC:
                    while ($i < $limit && $row = $this->result->fetch_assoc()) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case self::FETCH_ARRAY_NUM:
                    while ($i < $limit && $row = $this->result->fetch_array(MYSQLI_NUM)) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case self::FETCH_ARRAY_BOTH:
                    while ($i < $limit && $row = $this->result->fetch_array()) {
                        $this->data[$i++] = $row;
                    }
                    break;
                default:
                    $this->free();
                    throw new Exception\ResultException(
                        "Could not implement given `{$fetchType}` fetch type!");
            }

            // map result data
            if (isset($this->agent->mapper)
                && ($mapper = $this->agent->getMapper())
                && ($key = $this->result->fetch_field()->orgtable)) {
                $this->data = $mapper->map($key, $this->data);
            }
        }

        // free result
        $this->free();

        // dirty ways to detect last insert id for multiple inserts
        // good point! http://stackoverflow.com/a/15664201/362780
        $id  = $link->insert_id;
        $ids = $id ? [$id] : [];

        /**
         * // only last id
         * if ($id && $link->affected_rows > 1) {
         *     $id = ($id + $link->affected_rows) - 1;
         * }
         *
         * // all ids
         * if ($id && $link->affected_rows > 1) {
         *     for ($i = 0; $i < $link->affected_rows - 1; $i++) {
         *         $ids[] = $id + 1;
         *     }
         * }
         */

        // all ids (more tricky)
        if ($id && $link->affected_rows > 1) {
            $ids = range($id, ($id + $link->affected_rows) - 1);
        }

        // set properties
        $this->setId($ids);
        $this->setRowsCount($i);
        $this->setRowsAffected($link->affected_rows);

        return $this;
    }
}
