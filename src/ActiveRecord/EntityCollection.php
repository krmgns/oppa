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
 * @package    Oppa
 * @subpackage Oppa\ActiveRecord
 * @object     Oppa\ActiveRecord\EntityCollection
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class EntityCollection implements \Countable, \IteratorAggregate
{
    /**
     * Collection.
     * @var array
     */
    private $collection = [];

    /**
     * Add.
     * @param  Oppa\ActiveRecord\ActiveRecord $ar
     * @param  array                          $data
     * @return self
     */
    final public function add(ActiveRecord $ar, array $data = []): self
    {
        $this->collection[] = new Entity($ar, $data);

        return $this;
    }

    /**
     * Remove.
     * @param  int $i
     * @return void
     */
    final public function remove(int $i): void
    {
        unset($this->collection[$i]);
    }

    /**
     * Get.
     * @param  int $i
     * @return ?Oppa\ActiveRecord\Entity
     */
    final public function item($i): ?Entity
    {
        return $this->collection[$i] ?? null;
    }

    /**
     * First.
     * @return ?Oppa\ActiveRecord\Entity
     */
    final public function first(): ?Entity
    {
        return $this->item(0);
    }

    /**
     * Last.
     * @return ?Oppa\ActiveRecord\Entity
     */
    final public function last(): ?Entity
    {
        return $this->item(count($this->collection) - 1);
    }

    /**
     * Is empty.
     * @return bool
     */
    final public function isEmpty(): bool
    {
        return empty($this->collection);
    }

    /**
     * Count.
     * @return int
     */
    final public function count(): int
    {
        return count($this->collection);
    }

    /**
     * Get iterator.
     * @return \ArrayIterator
     */
    final public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->collection);
    }

    /**
     * Get collection.
     * @return array
     */
    final public function getCollection(): array
    {
        return $this->collection;
    }
}
