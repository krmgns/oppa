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

use Oppa\Agent\AgentInterface;

/**
 * @package    Oppa
 * @subpackage Oppa\Query\Result
 * @object     Oppa\Query\Result\Result
 * @author     Kerem Güneş <k-gun@mail.com>
 */
abstract class Result implements ResultInterface
{
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
     * Get agent.
     * @return Oppa\Agent\AgentInterface
     */
    final public function getAgent(): AgentInterface
    {
        return $this->agent;
    }

    /**
     * Get result.
     * @return object|resource
     */
    final public function getResult()
    {
        return $this->resource;
    }

    /**
     * Reset.
     * @return void
     */
    final public function reset(): void
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
        $fetchTypeConst = 'self::AS_'. strtoupper($fetchType);
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
    final public function setFetchType($fetchType): void
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
     * Set ids.
     * @param  array $ids
     * @return void
     */
    final public function setIds(array $ids): void
    {
        $this->ids = $ids;
    }

    /**
     * Get ids.
     * @return array
     */
    final public function getIds(): array
    {
        return $this->ids;
    }

    /**
     * Set id.
     * @param int|string $id
     */
    final public function setId($id): void
    {
        $this->ids[] = $id;
    }

    /**
     * Get id.
     * @return any
     */
    final public function getId()
    {
        return (false !== ($id = end($this->ids))) ? $id : null;
    }

    /**
     * Set rows count.
     * @param  int $count
     * @return void
     */
    final public function setRowsCount(int $count): void
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
     * @param  int $count
     * @return void
     */
    final public function setRowsAffected(int $count): void
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
     * Get data.
     * @return array
     */
    final public function getData(): array
    {
        return $this->data;
    }

    /**
     * To array.
     * @return array
     */
    final public function toArray(): array
    {
        $data = $this->data;
        foreach ($data as &$dat) {
            $dat = (array) $dat;
        }

        return $data;
    }

    /**
     * To object.
     * @return array
     */
    final public function toObject(): array
    {
        $data = $this->data;
        foreach ($data as &$dat) {
            $dat = (object) $dat;
        }

        return $data;
    }

    /**
     * To class.
     * @param  string $class
     * @return class
     */
    final public function toClass(string $class)
    {
        $data = $this->data;
        $class = new $class();
        foreach ($data as &$dat) {
            $datClass = clone $class;
            foreach ((array) $dat as $key => $value) {
                $datClass->{$key} = $value;
            }
            $dat = $datClass;
        }

        return $data;
    }

    /**
     * To JSON.
     * @param  int $options
     * @param  int $depth
     * @return string
     */
    final public function toJson(int $options = 0, int $depth = 512): string
    {
        return json_encode($this->data, $options, $depth);
    }

    /**
     * Item.
     * @param  int $i
     * @return any|null
     */
    final public function item(int $i)
    {
        return $this->data[$i] ?? null;
    }

    /**
     * Item first.
     * @return any
     */
    final public function itemFirst()
    {
        return $this->item(0);
    }

    /**
     * Item last.
     * @return any
     */
    final public function itemLast()
    {
        return $this->item(count($this->data) - 1);
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
