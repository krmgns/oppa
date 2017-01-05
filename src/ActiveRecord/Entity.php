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
     * Methods.
     * @var array
     */
    private $methods = [];

    /**
     * ActiveRecord.
     * @var Oppa\ActiveRecord\ActiveRecord
     */
    private $activeRecord;

    /**
     * Constructor.
     * @param Oppa\ActiveRecord\ActiveRecord $activeRecord
     * @param array                          $data
     */
    final public function __construct(ActiveRecord $activeRecord, array $data = [])
    {
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }

        // set owner active record
        $this->activeRecord = $activeRecord;
    }

    /**
     * Call.
     * @param  string $methodName
     * @param  array  $methodArgs
     * @return any
     * @throws \BadMethodCallException
     */
    final public function __call(string $methodName, array $methodArgs)
    {
        $methodClosure = $this->getMethod($methodName);
        if ($methodClosure) {
            return call_user_func_array($methodClosure, $methodArgs);
        }

        throw new \BadMethodCallException("Method '{$methodName}' does not exists on this entity!");
    }

    /**
     * Set.
     * @param  string $key
     * @param  any    $value
     * @return void
     */
    final public function __set(string $key, $value): void
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

        throw new InvalidKeyException("Given '{$key}' key is not found on this entity!");
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
    final public function __unset(string $key): void
    {
        unset($this->data[$key]);
    }

    /**
     * From array.
     * @param  array $data
     * @param  bool  $reset
     * @return void
     */
    final public function fromArray(array $data, bool $reset = false): void
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
     * To object.
     * @return \stdClass
     */
    final public function toObject(): \stdClass
    {
        return (object) $this->data;
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
     * @return ?int
     */
    final public function save(): ?int
    {
        return $this->activeRecord->save($this);
    }

    /**
     * Remove.
     * @return ?int
     */
    final public function remove(): ?int
    {
        $tablePrimary = $this->activeRecord->getTablePrimary();
        if ($this->__isset($tablePrimary)) {
            return $this->activeRecord->remove($this->__get($tablePrimary));
        }

        return null;
    }

    /**
     * Add method.
     * @param  string   $methodName
     * @param  callable $methodClosure
     * @return void
     */
    final public function addMethod(string $methodName, callable $methodClosure): void
    {
        $this->methods[strtolower($methodName)] = $methodClosure->bindTo($this);
    }

    /**
     * Get method.
     * @param  string $methodName
     * @return ?callable
     */
    final public function getMethod(string $methodName): ?callable
    {
        return $this->methods[strtolower($methodName)] ?? null;
    }

    /**
     * Get methods.
     * @return array
     */
    final public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get active record.
     * @return Oppa\ActiveRecord\ActiveRecord
     */
    final public function getActiveRecord(): ActiveRecord
    {
        return $this->activeRecord;
    }
}
