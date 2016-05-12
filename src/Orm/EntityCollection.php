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

namespace Oppa\Orm;

/**
 * @package    Oppa
 * @subpackage Oppa\Orm
 * @object     Oppa\Orm\EntityCollection
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class EntityCollection implements \Countable, \IteratorAggregate
{
    /**
     * Entity collection stack.
     * @var array
     */
    private $collection = [];

    /**
     * Add an entity.
     * @param  Oppa\Orm\Orm $orm
     * @param  array        $data
     * @return self
     */
    final public function add(Orm $orm, array $data = []): self
    {
        $this->collection[] = new Entity($orm, $data);

        return $this;
    }

    /**
     * Remove an entity.
     * @param  int $i
     * @return void
     */
    final public function remove($i)
    {
        unset($this->collection[$i]);
    }

    /**
     * Get an entity item.
     * @param  int $i
     * @return Oppa\Orm\Entity|null
     */
    final public function item($i)
    {
        return $this->collection[$i] ?? null;
    }

    /**
     * Get first entity item.
     * @return Oppa\Orm\Entity|null
     */
    final public function first()
    {
        return $this->item(0);
    }

    /**
     * Get last entity item.
     * @return Oppa\Orm\Entity|null
     */
    final public function last()
    {
        return $this->item(count($this->collection) - 1);
    }

    /**
     * Check entity collection is empty.
     * @return bool
     */
    final public function isFound(): bool
    {
        return !empty($this->collection);
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
}
