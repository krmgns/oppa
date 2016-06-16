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

use Oppa\Agent;

/**
 * @package    Oppa
 * @subpackage Oppa\Batch
 * @object     Oppa\Batch\Mysqli
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Mysqli extends Batch
{
    /**
     * Constructor.
     * @param Oppa\Agent\Mysqli $agent
     */
    final public function __construct(Agent\Mysqli $agent)
    {
        $this->agent = $agent;
    }

    /**
     * Lock.
     * @return void
     */
    final public function lock(): bool
    {
        return $this->agent->getResource()->autocommit(false);
    }

    /**
     * Unlock.
     * @return void
     */
    final public function unlock(): bool
    {
        return $this->agent->getResource()->autocommit(true);
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
     * Run.
     * @return Oppa\Batch\BatchInterface
     */
    final public function run(): BatchInterface
    {
        // no need to get excited
        if (empty($this->queue)) {
            return $this;
        }

        // get the big boss
        $resource = $this->agent->getResource();

        $start = microtime(true);

        foreach ($this->queue as $query) {
            // that what i see: clone is important in such actions
            $result = clone $this->agent->query($query);

            if ($result->getRowsAffected() > 0) {
                // this is also important for insert actions!
                $result->setId($resource->insert_id);

                $this->results[] = $result;
            }

            unset($result);
        }

        // go go go
        $resource->commit();

        $end = microtime(true);

        // calculate process time just for simple profiling
        $this->totalTime = (float) number_format((float) ($end - $start), 10);

        // even transactions are designed for insert/update/delete/replace
        // actions, let it be sure resetting the result object
        $this->agent->getResult()->reset();

        // forgot to call unlock(), hmmm?
        $resource->autocommit(true);

        return $this;
    }

    /**
     * Run query.
     * @param  string     $query
     * @param  array|null $params
     * @return Oppa\Batch\BatchInterface
     */
    final public function runQuery(string $query, array $params = null): BatchInterface
    {
        return $this->queue($query, $params)->run()->getResult(0);
    }

    /**
     * Cancel.
     * @return void
     */
    final public function cancel()
    {
        // get big boss
        $resource = $this->agent->getResource();

        // mayday mayday
        $resource->rollback();

        // free autocommits
        $resource->autocommit(true);

        $this->reset();
    }
}
