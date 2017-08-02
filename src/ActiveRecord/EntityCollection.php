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

namespace Oppa\ActiveRecord;

/**
 * @package Oppa
 * @object  Oppa\ActiveRecord\EntityCollection
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class EntityCollection implements \Countable, \IteratorAggregate
{
    /**
     * Collection.
     * @var array
     */
    private $collection = [];

    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Add.
     * @param  Oppa\ActiveRecord\ActiveRecord $activeRecord
     * @param  array                          $data
     * @return void
     */
    public function add(ActiveRecord $activeRecord, array $data = []): void
    {
        $this->collection[] = new Entity($activeRecord, $data);
    }

    /**
     * Add entity.
     * @param  Oppa\ActiveRecord\Entity $entity
     * @return void
     */
    public function addEntity(Entity $entity): void
    {
        $this->collection[] = $entity;
    }

    /**
     * Remove.
     * @param  int $i
     * @return void
     */
    public function remove(int $i): void
    {
        unset($this->collection[$i]);
    }

    /**
     * Get.
     * @param  int $i
     * @return ?Oppa\ActiveRecord\Entity
     */
    public function item($i): ?Entity
    {
        return $this->collection[$i] ?? null;
    }

    /**
     * Item first.
     * @return ?Oppa\ActiveRecord\Entity
     */
    public function itemFirst(): ?Entity
    {
        return $this->item(0);
    }

    /**
     * Item last.
     * @return ?Oppa\ActiveRecord\Entity
     */
    public function itemLast(): ?Entity
    {
        return $this->item(count($this->collection) - 1);
    }

    /**
     * Is empty.
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->collection);
    }

    /**
     * Count.
     * @return int
     */
    public function count(): int
    {
        return count($this->collection);
    }

    /**
     * Get iterator.
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->collection);
    }

    /**
     * Get collection.
     * @return array
     */
    public function getCollection(): array
    {
        return $this->collection;
    }
}
