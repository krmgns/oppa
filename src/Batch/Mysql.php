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
 * @object     Oppa\Batch\Mysql
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Mysql extends Batch
{
    /**
     * Constructor.
     * @param Oppa\Agent\Mysql $agent
     */
    final public function __construct(Agent\Mysql $agent)
    {
        $this->agent = $agent;
    }

    /**
     * Lock.
     * @return bool
     */
    final public function lock(): bool
    {
        return $this->agent->getResource()->autocommit(false);
    }

    /**
     * Unlock.
     * @return bool
     */
    final public function unlock(): bool
    {
        return $this->agent->getResource()->autocommit(true);
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

        $startTime = microtime(true);

        $resource = $this->agent->getResource();

        foreach ($this->queue as $query) {
            // @important (clone)
            $result = clone $this->agent->query($query);

            if ($result->getRowsAffected() > 0) {
                // @important
                $result->setIds([$resource->insert_id]);

                $this->results[] = $result;
            }

            unset($result);
        }

        // go go go
        $resource->commit();

        $this->totalTime = (float) number_format(microtime(true) - $startTime, 10);

        // even transactions are designed for insert/update/delete/replace
        // actions, let it be sure resetting the result object
        $this->agent->getResult()->reset();

        return $this;
    }

    /**
     * Cancel.
     * @return void
     */
    final public function cancel(): void
    {
        $resource = $this->agent->getResource();
        $resource->rollback(); // mayday mayday
        $resource->autocommit(true);

        $this->reset();
    }
}
