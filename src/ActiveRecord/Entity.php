<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace Oppa\ActiveRecord;

use Oppa\Util;
use Oppa\Exception\InvalidKeyException;

/**
 * @package Oppa
 * @object  Oppa\ActiveRecord\Entity
 * @author  Kerem Güneş <k-gun@mail.com>
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
    public function __construct(ActiveRecord $activeRecord, array $data = [])
    {
        // set owner active record
        $this->activeRecord = $activeRecord;

        $this->setData($data);
    }

    /**
     * Call.
     * @param  string $methodName
     * @param  array  $methodArgs
     * @return any
     * @throws \BadMethodCallException
     */
    public function __call(string $methodName, array $methodArgs)
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
    public function __set(string $key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Get.
     * @param  string $key
     * @return any
     * @throws Oppa\Exception\InvalidKeyException
     */
    public function __get(string $key)
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
    public function __isset(string $key)
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Unset.
     * @param  string $key
     * @return void
     */
    public function __unset(string $key)
    {
        unset($this->data[$key]);
    }

    /**
     * Set data.
     * @param  array $data
     * @return void
     */
    public function setData(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    /**
     * Get data.
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * To array.
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * To object.
     * @return \stdClass
     */
    public function toObject(): \stdClass
    {
        return (object) $this->data;
    }

    /**
     * Is found.
     * @return bool
     */
    public function isFound(): bool
    {
        return !empty($this->data);
    }

    /**
     * Is empty.
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * Save.
     * @return ?int
     */
    public function save(): ?int
    {
        return $this->activeRecord->save($this);
    }

    /**
     * Remove.
     * @return int
     */
    public function remove(): int
    {
        return $this->activeRecord->remove($this);
    }

    /**
     * Add method.
     * @param  string   $methodName
     * @param  callable $methodClosure
     * @return void
     */
    public function addMethod(string $methodName, callable $methodClosure): void
    {
        $this->methods[strtolower($methodName)] = $methodClosure->bindTo($this);
    }

    /**
     * Get method.
     * @param  string $methodName
     * @return ?callable
     */
    public function getMethod(string $methodName): ?callable
    {
        return $this->methods[strtolower($methodName)] ?? null;
    }

    /**
     * Get methods.
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get active record.
     * @return Oppa\ActiveRecord\ActiveRecord
     */
    public function getActiveRecord(): ActiveRecord
    {
        return $this->activeRecord;
    }

    /**
     * Has primary value.
     * @return bool
     */
    public function hasPrimaryValue(): bool
    {
        return isset($this->data[$this->activeRecord->getTablePrimary()]);
    }

    /**
     * Set primary value.
     * @param  int|string $primaryValue
     * @return void
     */
    public function setPrimaryValue($primaryValue): void
    {
        $this->data[$this->activeRecord->getTablePrimary()] = $primaryValue;
    }

    /**
     * Get primary value.
     * @return int|string|null
     */
    public function getPrimaryValue()
    {
        return $this->data[$this->activeRecord->getTablePrimary()] ?? null;
    }
}
