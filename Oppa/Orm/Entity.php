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

use \Oppa\Helper;
use \Oppa\Exception\Orm as Exception;

/**
 * @package    Oppa
 * @subpackage Oppa\Orm
 * @object     Oppa\Orm\Entity
 * @uses       Oppa\Helper, Oppa\Exception\Orm
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
final class Entity
{
    /**
     * Data stack.
     * @var array
     */
    private $data = [];

    /**
     * Owner ORM object
     * @var Oppa\Orm\Orm
     */
    private $orm;

    /**
     * Create a fresh Entity object.
     *
     * @param Oppa\Orm\Orm $orm
     * @param array        $data
     */
    final public function __construct(Orm $orm = null, array $data = []) {
        // set data
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }

        // set owner orm
        $this->orm = $orm;
    }

    /**
     * Call post-defined method for each entity.
     *
     * @param  string $method
     * @param  array  $arguments
     * @throws Oppa\Exception\Orm\MethodException
     * @return mixed
     */
    final public function __call($method, $arguments) {
        // check for method
        $method = strtolower($method);
        $methods = $this->orm->getBindingMethods();
        if (isset($methods[$method])) {
            $method = $methods[$method]->bindTo($this);
            return call_user_func_array($method, $arguments);
        }

        throw new Exception\MethodException('Method does not exists!');
    }

    /**
     * Set a data property.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    final public function __set($key, $value) {
        $this->data[$key] = $value;
    }

    /**
     * Get a data property.
     *
     * @param  string $key
     * @throws Oppa\Exception\Orm\ArgumentException
     * @return mixed
     */
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

    /**
     * Check a data property.
     *
     * @param  string  $key
     * @return boolean
     */
    final public function __isset($key) {
        return array_key_exists($key, $this->data);
    }

    /**
     * Remove a data property.
     *
     * @param  string $key
     * @return void
     */
    final public function __unset($key) {
        unset($this->data[$key]);
    }

    /**
     * Get all data stack.
     *
     * @return array
     */
    final public function toArray() {
        return $this->data;
    }

    /**
     * Check data stack is empty or not.
     *
     * @return boolean
     */
    final public function isFound() {
        return !empty($this->data);
    }

    /**
     * Save entity.
     *
     * @return integer
     */
    final public function save() {
        return $this->orm->save($this);
    }

    /**
     * Remove entity.
     *
     * @return integer But null if no entity found.
     */
    final public function remove() {
        $primaryKey = $this->orm->getPrimaryKey();
        if (isset($this->{$primaryKey})) {
            return $this->orm->remove($this->{$primaryKey});
        }
    }
}
