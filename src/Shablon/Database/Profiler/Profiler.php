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

namespace Oppa\Shablon\Database\Profiler;

/**
 * @package    Oppa
 * @subpackage Oppa\Shablon\Database\Profiler
 * @object     Oppa\Shablon\Database\Profiler\Profiler
 * @author     Kerem Güneş <k-gun@mail.com>
 */
abstract class Profiler
{
    /**
     * Profile key for connection.
     * @const int
     */
    const CONNECTION = 'connection';

    /**
     * Profile key for last query.
     * @const int
     */
    const LAST_QUERY = 'last_query';

    /**
     * Profile key for transaction.
     * @const int
     */
    const TRANSACTION = 'transaction'; // @notimplemented

    /**
     * Last query.
     * @var string
     */
    protected $lastQuery = '';

    /**
     * Query count.
     * @var int
     */
    protected $queryCount = 0;

    /**
     * Profiles.
     * @var array
     */
    protected $profiles = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Reset last query, query count and all profiles.
     * @return void
     */
    final public function reset()
    {
        $this->lastQuery  = '';
        $this->queryCount = 0;
        $this->profiles   = [];
    }

    /**
     * Get profile.
     * @param  string $key
     * @return any
     * @throws \Exception
     */
    public function getProfile(string $key)
    {
        if (isset($this->profiles[$key])) {
            return $this->profiles[$key];
        }

        throw new \Exception("Could not find a profile with given `{$key}` key!");
    }

    /**
     * Get all profiles.
     * @return array
     */
    public function getProfiles(): array
    {
        return $this->profiles;
    }

    /**
     * Set last query.
     * @param  string $query
     * @return void
     */
    final public function setLastQuery(string $query)
    {
        $this->lastQuery = $query;
    }

    /**
     * Get last query.
     * @return string
     */
    final public function getLastQuery(): string
    {
        return $this->lastQuery;
    }

    /**
     * Increase query count.
     * @return void
     */
    final public function increaseQueryCount()
    {
        ++$this->queryCount;
    }

    /**
     * Get query count.
     * @return int
     */
    final public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    /**
     * Action pattern.
     * @param string $key
     */
    abstract public function start(string $key);

    /**
     * Action pattern.
     * @param string $key
     */
    abstract public function stop(string $key);
}
