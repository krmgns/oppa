<?php namespace Oppa;

class Configuration
    implements \ArrayAccess
{
    protected $options = [];

    public function __construct(array $options = []) {
        if (!empty($options)) {
            $this->sets($options);
        }
    }

    public function __set($key, $value) {
        return $this->set($key, $value);
    }

    public function __get($key) {
        return $this->get($key);
    }

    public function __isset($key) {
        return $this->offsetExists($key);
    }

    public function __unset($key) {
        return $this->offsetUnset($key);
    }

    public function sets($options) {
        foreach ($options as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function set($key, $value) {
        $this->options[$key] = $value;
        return $this;
    }

    public function get($key, $defaultValue = null) {
        if ($this->offsetExists($key)) {
            return $this->options[$key];
        }
        return $defaultValue;
    }

    public function offsetSet($key, $value) {
        return $this->set($key, $value);
    }

    public function offsetGet($key) {
        return $this->get($key);
    }

    public function offsetExists($key) {
        return isset($this->options[$key]);
    }

    public function offsetUnset($key) {
        unset($this->options[$key]);
    }

    public function toArray() {
        return $this->options;
    }
}
