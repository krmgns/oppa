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

use Oppa\Util;

/**
 * @package    Oppa
 * @subpackage Oppa\Orm
 * @object     Oppa\Orm\Entity
 * @author     Kerem Güneş <k-gun@mail.com>
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
     * Constructor.
     * @param Oppa\Orm\Orm $orm
     * @param array        $data
     */
    final public function __construct(Orm $orm, array $data = [])
    {
        // set data
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }

        // set owner orm
        $this->orm = $orm;
    }

    /**
     * Call post-defined method for each entity.
     * @param  string $method
     * @param  array  $arguments
     * @return any
     * @throws \Exception
     */
    final public function __call(string $method, array $arguments)
    {
        // check for method
        $method = strtolower($method);
        $methods = $this->orm->getBindMethods();
        if (isset($methods[$method])) {
            $method = $methods[$method]->bindTo($this);
            return call_user_func_array($method, $arguments);
        }

        throw new \Exception('Method does not exists!');
    }

    /**
     * Set a data property.
     * @param  string $key
     * @param  any    $value
     * @return void
     */
    final public function __set(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Get a data property.
     * @param  string $key
     * @return any
     * @throws \Exception
     */
    final public function __get(string $key)
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }

        // check for camel-cased keys
        $keyCC = Util::upperToSnake($key);
        if (array_key_exists($keyCC, $this->data)) {
            return $this->data[$keyCC];
        }

        throw new \Exception("Given `{$key}` key is not found in this entity!");
    }

    /**
     * Check a data property.
     * @param  string $key
     * @return bool
     */
    final public function __isset(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Remove a data property.
     * @param  string $key
     * @return void
     */
    final public function __unset(string $key)
    {
        unset($this->data[$key]);
    }

    /**
     * Get all data stack.
     * @return array
     */
    final public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Check data stack is empty or not.
     * @return bool
     */
    final public function isFound(): bool
    {
        return !empty($this->data);
    }

    /**
     * Save entity.
     * @return any
     */
    final public function save()
    {
        return $this->orm->save($this);
    }

    /**
     * Remove entity.
     * @return int
     * @return void
     */
    final public function remove(): int
    {
        $primaryKey = $this->orm->getPrimaryKey();
        if (isset($this->{$primaryKey})) {
            return $this->orm->remove($this->{$primaryKey});
        }
    }
}
