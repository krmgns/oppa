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

namespace Oppa\Batch;

use Oppa\Agent\AgentInterface;
use Oppa\Query\Result\Result;

/**
 * @package    Oppa
 * @subpackage Oppa\Batch
 * @object     Oppa\Batch\Batch
 * @author     Kerem Güneş <k-gun@mail.com>
 */
abstract class Batch implements BatchInterface
{
    /**
     * Agent.
     * @var Oppa\Agent\AgentInterface
     */
    protected $agent;

    /**
     * Query queue.
     * @var array
     */
    protected $queue = [];

    /**
     * Query results.
     * @var array
     */
    protected $results = [];

    /**
     * Total transaction time.
     * @var float
     */
    protected $totalTime = 0.00;

    /**
     * Get agent.
     * @return Oppa\Agent\AgentInterface
     */
    final public function getAgent(): AgentInterface
    {
        return $this->agent;
    }

    /**
     * Get queue.
     * @return array
     */
    final public function getQueue(): array
    {
        return $this->queue;
    }

    /**
     * Get result.
     * @param  int $i
     * @return ?Oppa\Query\Result\Result
     */
    final public function getResult(int $i): ?Result
    {
        return $this->results[$i] ?? null;
    }

    /**
     * Get results.
     * @return array
     */
    final public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Get total time.
     * @return float
     */
    final public function getTotalTime(): float
    {
        return $this->totalTime;
    }

    /**
     * Reset.
     * @return void
     */
    final public function reset(): void
    {
        $this->queue = [];
        $this->results = [];
        $this->totalTime = 0.00;
    }

    /**
     * Queue.
     * @param  string     $query
     * @param  array|null $params
     * @return self
     */
    final public function queue(string $query, array $params = null): BatchInterface
    {
        $this->queue[] = $this->agent->prepare($query, $params);

        return $this;
    }

    /**
     * Run queue.
     * @param  string     $query
     * @param  array|null $params
     * @return Oppa\Batch\BatchInterface
     */
    final public function queueRun(string $query, array $params = null): BatchInterface
    {
        return $this->queue($query, $params)->run();
    }

    /**
     * Run query.
     * @deprecated
     */
    final public function runQuery()
    {
        $class = get_class($this);
        user_error("{$class}::runQuery() is deprecated, ".
            "use {$class}::queueRun() instead!", E_USER_DEPRECATED);

        return call_user_func_array([$this, 'queueRun'], func_get_args());
    }
}
