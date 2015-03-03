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

namespace Oppa\Database\Query;

use \Oppa\Exception\Database as Exception;

/**
 * @package    Oppa
 * @subpackage Oppa\Database\Query
 * @object     Oppa\Database\Query\Result
 * @uses       Oppa\Exception\Database
 * @extends    Oppa\Shablon\Database\Query\Result
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
abstract class Result
    extends \Oppa\Shablon\Database\Query\Result
{
    /**
     * Fetch results as object.
     * @const integer
     */
    const FETCH_OBJECT = 1; // @default

    /**
     * Fetch results as associative array.
     * @const integer
     */
    const FETCH_ARRAY_ASSOC = 2;

    /**
     * Fetch results as numarated array.
     * @const integer
     */
    const FETCH_ARRAY_NUM = 3;

    /**
     * Fetch results as both associative/numarated array.
     * @const integer
     */
    const FETCH_ARRAY_BOTH = 4;

    /**
     * Resource object.
     * @var object/resource
     */
    protected $result;

    /**
     * Result fetch type.
     * @var integer
     */
    protected $fetchType;

    /**
     * Result data stack.
     * @var array
     */
    protected $data = [];

    /**
     * Last inserted ID's.
     * @var array
     */
    protected $id = [];

    /**
     * Rows count that affected last select action.
     * @var integer
     */
    protected $rowsCount = 0;

    /**
     * Rows count that affected last update/delete etc action.
     * @var integer
     */
    protected $rowsAffected = 0;

    /**
     * Reset Result vars.
     *
     * @return void
     */
    final public function reset() {
        // reset data
        $this->data = [];
        // reset properties
        $this->id = [];
        $this->rowsCount = 0;
        $this->rowsAffected = 0;
    }

    /**
     * Set fetch type.
     *
     * @param  integer $fetchType
     * @throws Oppa\Exception\Database\ArgumentException
     * @return void
     */
    final public function setFetchType($fetchType) {
        // fetch type argument could be int, but not recommanded
        if (is_integer($fetchType)) {
            if (!in_array($fetchType, [1, 2, 3, 4])) {
                throw new Exception\ArgumentException(
                    "Given `{$fetchType}` fetch type is not implemented!");
            }
            $this->fetchType = $fetchType;
        }
        // or could be string as default like 'object', 'array_assoc', 'array_num'
        else {
            $fetchTypeConst = 'self::FETCH_'. strtoupper($fetchType);
            if (!defined($fetchTypeConst)) {
                throw new Exception\ArgumentException(
                    "Given `{$fetchType}` fetch type is not implemented!");
            }
            $this->fetchType = constant($fetchTypeConst);
        }
    }

    /**
     * Get fetch type.
     *
     * @return integer
     */
    final public function getFetchType() {
        return $this->fetchType;
    }

    /**
     * Get result data.
     *
     * @return array
     */
    final public function getData() {
        return $this->data;
    }

    /**
     * Set result id(s).
     *
     * @param  integer|array $id
     * @return void
     */
    final public function setId($id) {
        $this->id = (array) $id;
    }

    /**
     * Get id(s).
     *
     * @param  boolean $all Returns an array containing all ids.
     * @return integer|array
     */
    final public function getId($all = false) {
        return $all
            ? $this->id       // all insert ids
            : end($this->id); // only last insert id
    }

    /**
     * Set rows count for select actions.
     *
     * @param  integer $count;
     * @return void
     */
    final public function setRowsCount($count) {
        $this->rowsCount = $count;
    }

    /**
     * Get rows count for select actions.
     *
     * @return integer
     */
    final public function getRowsCount() {
        return $this->rowsCount;
    }

    /**
     * Set rows count that affected for update/delete etc actions.
     *
     * @param integer $count
     */
    final public function setRowsAffected($count) {
        $this->rowsAffected = $count;
    }

    /**
     * Get rows count that affected for update/delete etc actions.
     *
     * @return integer
     */
    final public function getRowsAffected() {
        return $this->rowsAffected;
    }

    /**
     * Count data (from \Countable).
     *
     * @return integer
     */
    final public function count() {
        return count($this->data);
    }

    /**
     * Generate iterator for iteration actions (from \IteratorAggregate)
     *
     * @return \ArrayIterator
     */
    final public function getIterator() {
        return new \ArrayIterator($this->data);
    }
}
