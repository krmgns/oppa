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

use Oppa\Util;
use Oppa\Exception\InvalidKeyException;

/**
 * @package    Oppa
 * @subpackage Oppa\ActiveRecord
 * @object     Oppa\ActiveRecord\Entity
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Entity
{
    /**
     * Data.
     * @var array
     */
    private $data = [];

    /**
     * ActiveRecord.
     * @var Oppa\ActiveRecord\ActiveRecord
     */
    private $ar;

    /**
     * Constructor.
     * @param Oppa\ActiveRecord\ActiveRecord $ar
     * @param array                          $data
     */
    final public function __construct(ActiveRecord $ar, array $data = [])
    {
        // set data
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }

        // set owner active record
        $this->ar = $ar;
    }

    /**
     * Call.
     * @param  string $method
     * @param  array  $arguments
     * @return any
     * @throws \BadMethodCallException
     */
    final public function __call(string $method, array $arguments)
    {
        // check for method
        $method = strtolower($method);
        $methods = $this->ar->getBindMethods();
        if (isset($methods[$method])) {
            $method = $methods[$method]->bindTo($this);
            return call_user_func_array($method, $arguments);
        }

        throw new \BadMethodCallException('Method does not exists!');
    }

    /**
     * Set.
     * @param  string $key
     * @param  any    $value
     * @return void
     */
    final public function __set(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Get.
     * @param  string $key
     * @return any
     * @throws Oppa\InvalidKeyException
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

        throw new InvalidKeyException("Given '{$key}' key is not found in this entity!");
    }

    /**
     * Isset.
     * @param  string $key
     * @return bool
     */
    final public function __isset(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Unset.
     * @param  string $key
     * @return void
     */
    final public function __unset(string $key)
    {
        unset($this->data[$key]);
    }

    /**
     * From array.
     * @param  array $data
     * @param  bool  $reset
     * @return void
     */
    final public function fromArray(array $data, bool $reset = false)
    {
        if ($reset) $this->data = [];

        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    /**
     * To array.
     * @return array
     */
    final public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Is found.
     * @return bool
     */
    final public function isFound(): bool
    {
        return !empty($this->data);
    }

    /**
     * Save.
     * @return any
     */
    final public function save()
    {
        return $this->ar->save($this);
    }

    /**
     * Remove.
     * @return int|null
     */
    final public function remove()
    {
        $tablePrimary = $this->ar->getTablePrimary();
        if ($this->__isset($tablePrimary)) {
            return $this->ar->remove($this->__get($tablePrimary));
        }
    }
}
