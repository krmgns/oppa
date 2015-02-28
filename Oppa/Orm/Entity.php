<?php namespace Oppa\Orm;

use \Oppa\Helper;
use \Oppa\Exception\Orm as Exception;

final class Entity
{
    private $data = [];

    final public function __construct(array $data = []) {
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    final public function __set($key, $value) {
        $this->data[$key] = $value;
    }

    final public function __get($key) {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        // check for camel-cased keys
        $key2 = Helper::camelcaseToUnderscore($key);
        if (array_key_exists($key2, $this->data)) {
            return $this->data[$key2];
        }

        throw new Exception\ArgumentException(
            "Given `{$key}` key is not found in this entity!");
    }

    final public function __isset($key) {
        return array_key_exists($key, $this->data);
    }

    final public function __unset($key) {
        unset($this->data[$key]);
    }

    final public function toArray() {
        return $this->data;
    }

    final public function isFound() {
        return !empty($this->data);
    }
}
