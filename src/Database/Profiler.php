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

namespace Oppa\Database;

/**
 * @package    Oppa
 * @subpackage Oppa\Database
 * @object     Oppa\Database\Profiler
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Profiler
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
    protected $lastQuery;

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
     * @return string|null
     */
    final public function getLastQuery()
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
     * Get profile.
     * @param  string $key
     * @return array
     * @throws \Exception
     */
    public function getProfile(string $key): array
    {
        if (isset($this->profiles[$key])) {
            return $this->profiles[$key];
        }

        throw new \Exception("Could not find a profile with given `{$key}` key!");
    }

    /**
     * Get profiles.
     * @return array
     */
    public function getProfiles(): array
    {
        return $this->profiles;
    }

    /**
     * Start.
     * @param  string $key
     * @return void
     */
    final public function start(string $key)
    {
        $this->profiles[$key] = [
            'start' => microtime(true),
            'stop'  => 0,
            'total' => 0,
        ];
    }

    /**
     * Stop.
     * @param  string $key
     * @return void
     * @throws \Exception
     */
    final public function stop(string $key)
    {
        if (!isset($this->profiles[$key])) {
            throw new \Exception("Could not find a '{$key}' profile key!");
        }

        $this->profiles[$key]['stop'] = microtime(true);
        $this->profiles[$key]['total'] = number_format(
            ((float) ($this->profiles[$key]['stop'] - $this->profiles[$key]['start'])), 10);
    }

    /**
     * Reset.
     * @return void
     */
    final public function reset()
    {
        $this->lastQuery = null;
        $this->queryCount = 0;
        $this->profiles = [];
    }
}
