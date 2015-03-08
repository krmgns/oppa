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

namespace Oppa\Orm;

/**
 * @package    Oppa
 * @subpackage Oppa\Orm
 * @object     Oppa\Orm\EntityCollection
 * @implements \Countable, \IteratorAggregate
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
final class EntityCollection
    implements \Countable, \IteratorAggregate
{
    /**
     * Entity collection stack.
     * @var array
     */
    private $collection = [];

    // final public function __construct() {}

    /**
     * Add an entity.
     *
     * @param  Oppa\Orm\Orm $orm
     * @param  array        $data
     * @return void
     */
    final public function add(Orm $orm, array $data) {
        $this->collection[] = new Entity($orm, $data);
    }

    /**
     * Remove an entity.
     *
     * @param  integer $i
     * @return void
     */
    final public function remove($i) {
        unset($this->collection[$i]);
    }

    /**
     * Get an entity item.
     *
     * @param  integer $i
     * @return Oppa\Orm\Entity
     */
    final public function item($i) {
        if (isset($this->collection[$i])) {
            return $this->collection[$i];
        }
    }

    /**
     * Get first entity item.
     *
     * @return Oppa\Orm\Entity
     */
    final public function first() {
        return $this->item(0);
    }

    /**
     * Get last entity item.
     *
     * @return Oppa\Orm\Entity
     */
    final public function last() {
        return $this->item(count($this->collection) - 1);
    }

    /**
     * Check entity collection is empty.
     *
     * @return boolean
     */
    final public function isFound() {
        return !empty($this->collection);
    }

    /**
     * Count entity collection (from \Countable).
     *
     * @return integer
     */
    final public function count() {
        return count($this->collection);
    }

    /**
     * Generate iterator for iteration actions (from \IteratorAggregate)
     *
     * @return \ArrayIterator
     */
    final public function getIterator() {
        return new \ArrayIterator($this->collection);
    }
}
