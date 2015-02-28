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

namespace Oppa;

/**
 * @package    Oppa
 * @object     Oppa\Configuration
 * @implements \ArrayAccess
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
final class Configuration
    implements \ArrayAccess
{
    /**
     * Options stack.
     * @var array
     */
    private $options = [];

    /**
     * Creates a Configuration object.
     *
     * @param array $options
     */
    final public function __construct(array $options = []) {
        if (!empty($options)) {
            foreach ($options as $key => $value) {
                $this->options[$key] = $value;
            }
        }
    }

    /**
     * Set an option.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return self
     */
    final public function __set($key, $value) {
        return $this->set($key, $value);
    }

    /**
     * Get an option.
     *
     * @param  string $key
     * @return mixed|null
     */
    final public function __get($key) {
        return $this->get($key);
    }


    /**
     * Check an option.
     *
     * @param  string $key
     * @return boolean
     */
    final public function __isset($key) {
        return $this->offsetExists($key);
    }

    /**
     * Remove an option.
     *
     * @param  string $key
     * @return void
     */
    final public function __unset($key) {
        $this->offsetUnset($key);
    }


    /**
     * Set an option.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return self
     */
    final public function set($key, $value) {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Get an option.
     *
     * @param  string $key
     * @param  mixed  $defaultValue
     * @return mixed|null
     */
    final public function get($key, $defaultValue = null) {
        if ($this->offsetExists($key)) {
            return $this->options[$key];
        }

        return $defaultValue;
    }

    /**
     * Set an option.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return self
     */
    final public function offsetSet($key, $value) {
        return $this->set($key, $value);
    }

    /**
     * Get an option.
     *
     * @param  string $key
     * @return mixed|null
     */
    final public function offsetGet($key) {
        return $this->get($key);
    }

    /**
     * Check an option.
     *
     * @param  string $key
     * @return boolean
     */
    final public function offsetExists($key) {
        return isset($this->options[$key]);
    }

    /**
     * Get an option.
     *
     * @param  string $key
     * @return void
     */
    final public function offsetUnset($key) {
        unset($this->options[$key]);
    }

    /**
     * Get all options as array.
     *
     * @return array
     */
    final public function toArray() {
        return $this->options;
    }
}
