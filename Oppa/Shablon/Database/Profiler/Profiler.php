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
 * @version    v1.1
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
     * Profile key for last query.
     * @const integer
     */
    const LAST_QUERY = 2;

    /**
     * Profile key for transaction.
     * @const integer
     */
    const TRANSACTION = 3; // @notimplemented

    /**
     * Last query.
     * @var string
     */
    protected $lastQuery;

    /**
     * Query count.
     * @var integer
     */
    protected $queryCount = 0;

    /**
     * Profile stack.
     * @var array
     */
    protected $profiles = [];

    /**
     * Create a Profiler object resetting all stuff.
     */
    public function __construct() {
        $this->reset();
    }

    /**
     * Get invisible properties.
     *
     * @param  string $name
     * @throws Oppa\Exception\Database\ArgumentException
     * @return mixed
     */
    public function __get($name) {
        if ($name == 'lastQuery' || $name == 'queryCount') {
            return $this->{$name};
        }

        throw new Exception\ArgumentException('Undefined property!');
    }

    /**
     * Reset last query, query count and all profiles.
     *
     * @return void
     */
    final public function reset() {
        $this->lastQuery  = null;
        $this->queryCount = 0;
        $this->profiles   = [];
    }

    /**
     * Get profile.
     *
     * @param  string $key
     * @throws Oppa\Exception\Database\ArgumentException
     * @return mixed
     */
    public function getProfile($key) {
        if (isset($this->profiles[$key])) {
            return $this->profiles[$key];
        }

        throw new Exception\ArgumentException(
            "Could not find a profile with given `{$key}` key!");
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
     * Set last query.
     *
     * @param  string $query
     * @return void
     */
    final public function setLastQuery($query) {
        $this->lastQuery = $query;
    }

    /**
     * Get last query.
     *
     * @return string|null
     */
    final public function getLastQuery() {
        return $this->lastQuery;
    }

    /**
     * Increase query count.
     *
     * @return void
     */
    final public function increaseQueryCount() {
        ++$this->queryCount;
    }

    /**
     * Get query count.
     *
     * @return integer
     */
    final public function getQueryCount() {
        return $this->queryCount;
    }

    /**
     * Action pattern.
     *
     * @param string $key
     */
    abstract public function start($key);

    /**
     * Action pattern.
     *
     * @param string $key
     */
    abstract public function stop($key);
}
