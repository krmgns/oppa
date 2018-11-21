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
    public const CONNECTION  = 'connection',
                 QUERY       = 'query',
                 TRANSACTION = 'transaction'; // @notimplemented

    /**
     * Profiles.
     * @var array
     */
    private $profiles = [];

    /**
     * Query count.
     * @var int
     */
    private $queryCount = 0;

    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Get profile.
     * @param  string $key
     * @return array
     * @throws Oppa\Exception\InvalidKeyException
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
    public function addQuery(string $query): void
    {
        $this->profiles[self::QUERY][++$this->queryCount]['string'] = $query;
    }

    /**
     * Get query.
     * @param  int $i
     * @return ?array
     */
    public function getQuery(int $i): ?array
    {
        return $this->profiles[self::QUERY][$i] ?? null;
    }

    /**
     * Get query string.
     * @param  int $i
     * @return ?string
     */
    public function getQueryString(int $i): ?string
    {
        return $this->profiles[self::QUERY][$i]['string'] ?? null;
    }

    /**
     * Get last query.
     * @return ?array
     */
    public function getLastQuery(): ?array
    {
        return $this->profiles[self::QUERY][$this->queryCount] ?? null;
    }

    /**
     * Get last query string.
     * @return ?string
     */
    public function getLastQueryString(): ?string
    {
        return $this->profiles[self::QUERY][$this->queryCount]['string'] ?? null;
    }

    /**
     * Get query count.
     * @return int
     */
    public function getQueryCount(): int
    {
        return $this->queryCount;
    }

    /**
     * Start.
     * @param  string $key
     * @return void
     * @throws Oppa\Exception\InvalidKeyException
     */
    public function start(string $key): void
    {
        $startTime = microtime(true);
        switch ($key) {
            case self::CONNECTION:
            case self::TRANSACTION:
                $this->profiles[$key]['start'] = $startTime;
                $this->profiles[$key]['stop'] = 0.00;
                $this->profiles[$key]['total'] = 0.00;
                break;
            case self::QUERY:
                $i = $this->queryCount;
                if (isset($this->profiles[$key][$i])) {
                    $this->profiles[$key][$i] += [
                        'start' => $startTime, 'stop' => 0.00, 'total' => 0.00
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
     * @throws Oppa\Exception\InvalidKeyException
     */
    public function stop(string $key): void
    {
        if (!isset($this->profiles[$key])) {
            throw new InvalidKeyException("Could not find a profile with given key '{$key}'!");
        }

        $stopTime = microtime(true);
        switch ($key) {
            case self::CONNECTION:
            case self::TRANSACTION:
                $this->profiles[$key]['stop'] = $stopTime;
                $this->profiles[$key]['total'] = (float) number_format(
                    $stopTime - $this->profiles[$key]['start'], 10);
                break;
            case self::QUERY:
                $i = $this->queryCount;
                if (isset($this->profiles[$key][$i])) {
                    $this->profiles[$key][$i]['stop'] = $stopTime;
                    $this->profiles[$key][$i]['total'] = (float) number_format(
                        $stopTime - $this->profiles[$key][$i]['start'], 10);
                }
                break;
            default:
                throw new InvalidKeyException("Unimplemented key '{$key}' given!");
        }
    }

    /**
     * Reset.
     * @return void
     */
    public function reset(): void
    {
        $this->profiles = [];
        $this->queryCount = 0;
    }
}
