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
