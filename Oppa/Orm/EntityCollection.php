<?php namespace Oppa\Orm;

use \Oppa\Helper;
use \Oppa\Exception\Orm as Exception;

final class EntityCollection
    implements \Countable, \IteratorAggregate
{
    private $collection = [];

    final public function __construct() {}

    final public function add(array $data) {
        $this->collection[] = new Entity($data);
    }

    final public function remove($i) {
        unset($this->collection[$i]);
    }

    final public function item($i) {
        if (isset($this->collection[$i])) {
            return $this->collection[$i];
        }
    }

    final public function first() {
        return $this->item(0);
    }

    final public function last() {
        return $this->item(count($this->collection) - 1);
    }

    final public function isFound() {
        return !empty($this->collection);
    }

    final public function count() {
        return count($this->collection);
    }

    final public function getIterator() {
        return new \ArrayIterator($this->collection);
    }
}
