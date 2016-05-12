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

namespace Oppa\Query\Result;

/**
 * @package    Oppa
 * @subpackage Oppa\Query\Result
 * @object     Oppa\Query\Result\Result
 * @author     Kerem Güneş <k-gun@mail.com>
 */
abstract class Result implements ResultInterface
{
    /**
     * Fetch types.
     * @const int
     */
    const FETCH_OBJECT      = 1, // @default
          FETCH_ARRAY_ASSOC = 2,
          FETCH_ARRAY_NUM   = 3,
          FETCH_ARRAY_BOTH  = 4;

    /**
     * Agent.
     * @var Oppa\Agent\AgentInterface
     */
    protected $agent;

    /**
     * Resource.
     * @var object|resource
     */
    protected $result;

    /**
     * Fetch type.
     * @var int
     */
    protected $fetchType;

    /**
     * Data.
     * @var array
     */
    protected $data = [];

    /**
     * Id(s).
     * @var array
     */
    protected $id = [];

    /**
     * Rows count.
     * @var int
     */
    protected $rowsCount = 0;

    /**
     * Rows affected.
     * @var int
     */
    protected $rowsAffected = 0;

    /**
     * Reset.
     * @return void
     */
    final public function reset()
    {
        // reset data
        $this->data = [];

        // reset properties
        $this->id = [];
        $this->rowsCount = 0;
        $this->rowsAffected = 0;
    }

    /**
     * Detect fetch type.
     * @param  int|string $fetchType
     * @return int
     * @throws Oppa\InvalidValueException
     */
    final public function detectFetchType($fetchType): int
    {
        // fetch type could be int, but not recommanded
        if (is_integer($fetchType)) {
            if (!in_array($fetchType, [1, 2, 3, 4])) {
                throw new InvalidValueException("Given '{$fetchType}' fetch type is not implemented!");
            }

            return $fetchType;
        }

        // or could be string as default like 'object', 'array_assoc' etc.
        $fetchTypeConst = 'self::FETCH_'. strtoupper($fetchType);
        if (!defined($fetchTypeConst)) {
            throw new InvalidValueException("Given '{$fetchType}' fetch type is not implemented!");
        }

        return constant($fetchTypeConst);
    }

    /**
     * Set fetch type.
     * @param  int|string $fetchType
     * @return void
     */
    final public function setFetchType($fetchType)
    {
        $this->fetchType = $this->detectFetchType($fetchType);
    }

    /**
     * Get fetch type.
     * @return int
     */
    final public function getFetchType(): int
    {
        return $this->fetchType;
    }

    /**
     * Set result id(s).
     * @param  int|array $id
     * @return void
     */
    final public function setId($id)
    {
        $this->id = (array) $id;
    }

    /**
     * Get id(s).
     * @param  bool $all Returns an array containing all ids.
     * @return int|array|null
     */
    final public function getId(bool $all = false)
    {
        if (!$all) {
            // only last insert id
            return (false !== ($id = end($this->id))) ? $id : null;
        }

        // all insert ids
        return $this->id;
    }

    /**
     * Set rows count.
     * @param  int $count
     * @return void
     */
    final public function setRowsCount(int $count)
    {
        $this->rowsCount = $count;
    }

    /**
     * Get rows count.
     * @return int
     */
    final public function getRowsCount(): int
    {
        return $this->rowsCount;
    }

    /**
     * Set rows affected.
     * @param int $count
     */
    final public function setRowsAffected(int $count)
    {
        $this->rowsAffected = $count;
    }

    /**
     * Get rows affected.
     * @return int
     */
    final public function getRowsAffected(): int
    {
        return $this->rowsAffected;
    }

    /**
     * Get result data.
     * @param  int $i
     * @return any
     */
    final public function getData(int $i = null)
    {
        if ($i !== null) {
            return isset($this->data[$i])
                ? $this->data[$i] : null;
        }

        return $this->data;
    }

    /**
     * Get first data item.
     * @return any
     */
    final public function first()
    {
        return $this->getData(0);
    }

    /**
     * Get last data item.
     * @return any
     */
    final public function last()
    {
        return $this->getData(count($this->data) - 1);
    }

    /**
     * Count.
     * @return int
     */
    final public function count(): int
    {
        return count($this->data);
    }

    /**
     * Get iterator.
     * @return \ArrayIterator
     */
    final public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->data);
    }
}
