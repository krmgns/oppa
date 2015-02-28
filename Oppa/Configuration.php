<?php namespace Oppa;

class Configuration
    implements \ArrayAccess
{
    private $options = [];

    final public function __construct(array $options = []) {
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $this->options[$key] = $value;
            }
        }
    }

    final public function __set($key, $value) {
        return $this->set($key, $value);
    }

    final public function __get($key) {
        return $this->get($key);
    }

    final public function __isset($key) {
        return $this->offsetExists($key);
    }

    final public function __unset($key) {
        return $this->offsetUnset($key);
    }

    final public function set($key, $value) {
        $this->options[$key] = $value;
        return $this;
    }

    final public function get($key, $defaultValue = null) {
        if ($this->offsetExists($key)) {
            return $this->options[$key];
        }
        return $defaultValue;
    }

    final public function offsetSet($key, $value) {
        return $this->set($key, $value);
    }

    final public function offsetGet($key) {
        return $this->get($key);
    }

    final public function offsetExists($key) {
        return isset($this->options[$key]);
    }

    final public function offsetUnset($key) {
        unset($this->options[$key]);
    }

    final public function toArray() {
        return $this->options;
    }
}
