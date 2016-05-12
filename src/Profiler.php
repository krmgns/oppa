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

namespace Oppa;

use Oppa\Exception\InvalidKeyException;

/**
 * @package Oppa
 * @object  Oppa\Profiler
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Profiler
{
    /**
     * Profile keys.
     * @const string
     */
    const CONNECTION  = 'connection',
          QUERY       = 'query',
          TRANSACTION = 'transaction'; // @notimplemented

    /**
     * Profiles.
     * @var array
     */
    protected $profiles = [];

    /**
     * Query count.
     * @var int
     */
    protected $queryCount = 0;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Get profile.
     * @param  string $key
     * @return array
     * @throws Oppa\InvalidKeyException
     */
    public function getProfile(string $key): array
    {
        if (isset($this->profiles[$key])) {
            return $this->profiles[$key];
        }

        throw new InvalidKeyException("Could not find a profile with given '{$key}' key!");
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
     * Add query.
     * @param  string $query
     * @return void
     */
    final public function addQuery(string $query)
    {
        $this->profiles[self::QUERY][++$this->queryCount]['string'] = $query;
    }

    /**
     * Get last query.
     * @return string|null
     */
    final public function getLastQuery()
    {
        return $this->profiles[self::QUERY][$this->queryCount]['string'] ?? null;
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
     * Start.
     * @param  string $key
     * @return void
     * @throws Oppa\InvalidKeyException
     */
    final public function start(string $key)
    {
        switch ($key) {
            case self::CONNECTION:
            case self::TRANSACTION:
                $this->profiles[$key] = [
                    'start' => microtime(true), 'stop' => 0, 'total' => 0,
                ];
                break;
            case self::QUERY:
                if (isset($this->profiles[self::QUERY][$this->queryCount])) {
                    $this->profiles[self::QUERY][$this->queryCount] += [
                        'start' => microtime(true), 'stop' => 0, 'total' => 0,
                    ];
                }
                break;
            default:
                throw new InvalidKeyException("Unimplemented key '{$key}' given!");
        }
    }

    /**
     * Stop.
     * @param  string $key
     * @return void
     * @throws Oppa\InvalidKeyException
     */
    final public function stop(string $key)
    {
        if (!isset($this->profiles[$key])) {
            throw new InvalidKeyException("Could not find a '{$key}' profile key!");
        }

        switch ($key) {
            case self::CONNECTION:
            case self::TRANSACTION:
                $this->profiles[$key]['stop'] = microtime(true);
                $this->profiles[$key]['total'] = (float) number_format(
                    $this->profiles[$key]['stop'] - $this->profiles[$key]['start'], 10);
                break;
            case self::QUERY:
                if (isset($this->profiles[self::QUERY][$this->queryCount])) {
                    $this->profiles[self::QUERY][$this->queryCount]['stop'] = microtime(true);
                    $this->profiles[self::QUERY][$this->queryCount]['total'] = (float) number_format(
                        $this->profiles[self::QUERY][$this->queryCount]['stop'] -
                        $this->profiles[self::QUERY][$this->queryCount]['start'], 10);
                }
                break;
        }
    }

    /**
     * Reset.
     * @return void
     */
    final public function reset()
    {
        $this->profiles = [];
    }
}
