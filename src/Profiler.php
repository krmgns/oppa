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
