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

namespace Oppa;

/**
 * @package Oppa
 * @object  Oppa\Config
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Config implements \ArrayAccess
{
    /**
     * Options.
     * @var array
     */
    private $options = [];

    /**
     * Constructor.
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $this->options[$key] = $value;
            }
        }
    }

    /**
     * Set.
     * @param  string $key
     * @param  any    $value
     * @return self
     */
    public function __set(string $key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * Get.
     * @param  string $key
     * @return any
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }


    /**
     * Isset.
     * @param  string $key
     * @return bool
     */
    public function __isset(string $key)
    {
        return isset($this->options[$key]);
    }

    /**
     * Unset.
     * @param  string $key
     * @return void
     */
    public function __unset(string $key)
    {
        unset($this->options[$key]);
    }


    /**
     * Set.
     * @param  string $key
     * @param  any    $value
     * @return self
     */
    public function set(string $key, $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Get.
     * @param  string $key
     * @param  any    $value
     * @return any
     */
    public function get(string $key, $value = null)
    {
        if ($this->__isset($key)) {
            $value = $this->options[$key];
        }

        return $value;
    }

    /**
     * Set.
     * @param  int|string $key
     * @param  any $value
     * @return self
     */
    public function offsetSet($key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * Get.
     * @param  int|string $key
     * @return any
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Check.
     * @param  int|string $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->__isset($key);
    }

    /**
     * Remove.
     * @param  int|string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->__unset($key);
    }

    /**
     * To array.
     * @return array
     */
    public function toArray(): array
    {
        return $this->options;
    }
}
