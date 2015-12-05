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

namespace Oppa\Database\Batch;

use \Oppa\Database\Connector\Agent;

/**
 * @package    Oppa
 * @subpackage Oppa\Database\Batch
 * @object     Oppa\Database\Batch\Mysqli
 * @uses       Oppa\Database\Connector\Agent
 * @extends    Oppa\Shablon\Database\Batch\Batch
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
final class Mysqli
    extends \Oppa\Shablon\Database\Batch\Batch
{
    /**
     * Create a fresh Mysqli object.
     *
     * @param Oppa\Database\Connector\Agent\Mysqli $agent
     */
    final public function __construct(Agent\Mysqli $agent) {
        $this->agent = $agent;
    }

    /**
     * Lock autocommit.
     *
     * @return void
     */
    final public function lock() {
        $this->agent->getLink()->autocommit(false);
    }

    /**
     * Unlock autocommit.
     *
     * @return void
     */
    final public function unlock() {
        $this->agent->getLink()->autocommit(true);
    }

    /**
     * Add a new query queue.
     *
     * @param  string     $query
     * @param  array|null $params
     * @return void
     */
    final public function queue($query, array $params = null) {
        $this->queue[] = $this->agent->prepare($query, $params);
    }

    /**
     * Try to commit all queries.
     *
     * @return void
     */
    final public function run() {
        // no need to get excited
        if (empty($this->queue)) {
            return;
        }

        // get big boss
        $link = $this->agent->getLink();

        // keep start time
        $start = microtime(true);

        foreach ($this->queue as $query) {
            // that what i see: clone is important in such actions
            $result = clone $this->agent->query($query);

            if ($result->getRowsAffected()) {
                // this is also important for insert actions!
                $result->setId($link->insert_id);

                $this->result[] = $result;
            }

            unset($result);
        }

        // go go go
        $link->commit();

        // keep end time
        $stop = microtime(true);

        // calculate process time just for simple profiling
        $this->totalTime = number_format((float) ($stop - $start), 10);

        // even transactions are designed for insert/update/delete/replace
        // actions, let it be sure resetting the result object
        $this->agent->getResult()->reset();

        // forgot to call unlock(), hmmm?
        $link->autocommit(true);
    }

    /**
     * Cancel transaction and do rollback.
     *
     * @return void
     */
    final public function cancel() {
        $this->reset();

        // get big boss
        $link = $this->agent->getLink();

        // mayday mayday
        $link->rollback();

        // free autocommits
        $link->autocommit(true);
    }
}
