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
     * Query result.
     * @var array
     */
    protected $result = [];

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
     * @return array
     */
    final public function getResult(): array
    {
        return $this->result;
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
    final public function reset()
    {
        $this->queue = [];
        $this->result = [];
        $this->totalTime = 0.00;
    }
}
