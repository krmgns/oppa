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

namespace Oppa\Shablon\Database\Batch;

/**
 * @package    Oppa
 * @subpackage Oppa\Shablon\Database\Batch
 * @object     Oppa\Shablon\Database\Batch\Batch
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
abstract class Batch
{
    /**
     * Agent object.
     * @var Oppa\Database\Connector\Agent
     */
    protected $agent;

    /**
     * Query queue stack.
     * @var array
     */
    protected $queue = [];

    /**
     * Query result stack.
     * @var array
     */
    protected $result = [];

    /**
     * Total transaction time.
     * @var float
     */
    protected $totalTime = 0.00;

    /**
     * Reset stacks and total transaction time.
     *
     * @return void
     */
    public function reset() {
        $this->queue = [];
        $this->result = [];
        $this->totalTime = 0.00;
    }

    /**
     * Get query queue stack.
     *
     * @return array
     */
    public function getQueue() {
        return $this->queue;
    }

    /**
     * Get query result stack.
     *
     * @return array
     */
    public function getResult() {
        return $this->result;
    }

    /**
     * Get total process time.
     *
     * @return float
     */
    public function getTotalTime() {
        return $this->totalTime;
    }

    /**
     * Action pattern.
     */
    abstract public function lock();

    /**
     * Action pattern.
     */
    abstract public function unlock();

    /**
     * Action pattern.
     *
     * @param string $query
     * @param array  $params
     */
    abstract public function queue($query, array $params = null);

    /**
     * Action pattern.
     */
    abstract public function run();

    /**
     * Action pattern.
     */
    abstract public function cancel();
}
