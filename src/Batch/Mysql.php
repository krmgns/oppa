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
     * Agent resource.
     * @var \mysqli
     */
    private $agentResource;

    /**
     * Constructor.
     * @param Oppa\Agent\Mysql $agent
     */
    final public function __construct(Agent\Mysql $agent)
    {
        $this->agent = $agent;
        // shortcut
        $this->agentResource = $agent->getResource()->getObject();
    }

    /**
     * Lock.
     * @return bool
     */
    final public function lock(): bool
    {
        return $this->agentResource->autocommit(false);
    }

    /**
     * Unlock.
     * @return bool
     */
    final public function unlock(): bool
    {
        return $this->agentResource->autocommit(true);
    }

    /**
     * Start.
     * @return bool
     */
    final protected function start(): bool
    {
        return $this->agentResource->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
    }

    /**
     * End.
     * @return bool
     */
    final protected function end(): bool
    {
        return $this->agentResource->commit();
    }

    /**
     * Undo.
     * @return void
     */
    final public function undo(): void
    {
        // mayday mayday
        $this->agentResource->rollback();

        $this->reset();
    }

    /**
     * Get agent resource.
     * @return \mysqli
     */
    final public function getAgentResource(): \mysqli
    {
        return $this->agentResource;
    }
}
