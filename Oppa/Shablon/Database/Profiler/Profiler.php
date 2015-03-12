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

namespace Oppa\Shablon\Database\Profiler;

use \Oppa\Exception\Database as Exception;

/**
 * @package    Oppa
 * @subpackage Oppa\Shablon\Database\Profiler
 * @object     Oppa\Shablon\Database\Profiler\Profiler
 * @uses       Oppa\Exception
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
abstract class Profiler
{
    /**
     * Profile key for connection.
     * @const integer
     */
    const CONNECTION = 1;

    /**
     * Profile key for transaction.
     * @const integer
     */
    const TRANSACTION = 2; // @notimplemented

    /**
     * Profile key for last query.
     * @const integer
     */
    const LAST_QUERY = 3;

    /**
     * Property key for query count.
     * @const integer
     */
    const PROP_QUERY_COUNT = 10;

    /**
     * Property key for last query.
     * @const integer
     */
    const PROP_LAST_QUERY = 11;

    /**
     * Profile stack.
     * @var array
     */
    protected $profiles = [];

    /**
     * Property stack.
     * @var array
     */
    protected $properties = [];

    /**
     * Get profile.
     *
     * @param  string $name
     * @throws Oppa\Exception\Database\ArgumentException
     * @return mixed
     */
    public function getProfile($name) {
        if (isset($this->profiles[$name])) {
            return $this->profiles[$name];
        }

        throw new Exception\ArgumentException(
            "Could not find a profile with given `{$name}` name.");
    }

    /**
     * Get all profiles.
     *
     * @return array
     */
    public function getProfileAll() {
        return $this->profiles;
    }

    /**
     * Action pattern.
     *
     * @param string $name
     * @param mixed  $value
     */
    abstract public function setProperty($name, $value = null);

    /**
     * Action pattern.
     *
     * @param string $name
     */
    abstract public function getProperty($name);

    /**
     * Action pattern.
     *
     * @param string $name
     */
    abstract public function start($name);

    /**
     * Action pattern.
     *
     * @param string $name
     */
    abstract public function stop($name);
}
