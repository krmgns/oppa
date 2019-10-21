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
        $this->profiles['query'][++$this->queryCount]['string'] = $query;
    }

    /**
     * Get query.
     * @param  int $i
     * @return ?array
     */
    public function getQuery(int $i): ?array
    {
        return $this->profiles['query'][$i] ?? null;
    }

    /**
     * Get query string.
     * @param  int $i
     * @return ?string
     */
    public function getQueryString(int $i): ?string
    {
        return $this->profiles['query'][$i]['string'] ?? null;
    }

    /**
     * Get query time.
     * @param  int $i
     * @return ?float
     */
    public function getQueryTime(int $i): ?float
    {
        return $this->profiles['query'][$i]['time'] ?? null;
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
     * Get last query.
     * @param  string|null $key
     * @return array|string|float|null
     */
    public function getLastQuery(string $key = null)
    {
        return $key ? $this->profiles['query'][$this->queryCount][$key] ?? null
            : $this->profiles['query'][$this->queryCount] ?? null;
    }

    /**
     * Get last query string.
     * @return ?string
     */
    public function getLastQueryString(): ?string
    {
        return $this->getLastQuery('string');
    }

    /**
     * Get last query time.
     * @return ?float
     */
    public function getLastQueryTime(): ?float
    {
        return $this->getLastQuery('time');
    }

    /**
     * Get total time.
     * @param  bool $timeOnly
     * @return float|string|null
     */
    public function getTotalTime(bool $timeOnly = true)
    {
        if ($this->profiles == null) {
            return null;
        }

        $totalTime = 0.00;
        $totalTimeString = '';
        if (isset($this->profiles['connection'])) {
            $totalTime += $this->profiles['connection']['time'];
            if (!$timeOnly) {
                $totalTimeString .= "connection({$totalTime})";
            }
        }

        if (isset($this->profiles['query'])) {
            foreach ($this->profiles['query'] as $i => $profile) {
                $totalTime += $profile['time'];
                if (!$timeOnly) {
                    $totalTimeString .= " query({$i}, {$profile['time']})";
                }
            }
        }

        if (!$timeOnly) {
            $totalTimeString .= " total({$totalTime})";
        }

        return $timeOnly ? $totalTime : $totalTimeString;
    }

    /**
     * Start.
     * @param  string $key
     * @return void
     * @throws Oppa\Exception\InvalidKeyException
     */
    public function start(string $key): void
    {
        $start = microtime(true);
        switch ($key) {
            case 'connection':
                $this->profiles[$key] = [
                    'start' => $start, 'end' => 0.00, 'time' => 0.00
                ];
                break;
            case 'query':
                $i = $this->queryCount;
                if (isset($this->profiles[$key][$i])) {
                    $this->profiles[$key][$i] += [
                        'start' => $start, 'end' => 0.00, 'time' => 0.00
                    ];
                }
                break;
            default:
                throw new InvalidKeyException("Unimplemented key '{$key}' given, available keys are"
                    ." 'connection,query' only!");
        }
    }

    /**
     * End.
     * @param  string $key
     * @return void
     * @throws Oppa\Exception\InvalidKeyException
     */
    public function end(string $key): void
    {
        if (!isset($this->profiles[$key])) {
            throw new InvalidKeyException("Could not find a profile with given '{$key}' key!");
        }

        $end = microtime(true);
        switch ($key) {
            case 'connection':
                $this->profiles['connection']['end'] = $end;
                $this->profiles['connection']['time'] = round($end - $this->profiles['connection']['start'], 10);
                break;
            case 'query':
                $i = $this->queryCount;
                if (isset($this->profiles['query'][$i])) {
                    $this->profiles['query'][$i]['end'] = $end;
                    $this->profiles['query'][$i]['time'] = round($end - $this->profiles['query'][$i]['start'], 10);
                }
                break;
            default:
                throw new InvalidKeyException("Unimplemented key '{$key}' given, available keys are"
                    ." 'connection,query' only!");
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
