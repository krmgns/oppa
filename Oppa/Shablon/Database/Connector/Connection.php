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

namespace Oppa\Shablon\Database\Connector;

/**
 * @package    Oppa
 * @subpackage Oppa\Shablon\Database\Connector
 * @object     Oppa\Shablon\Database\Connector\Connection
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
abstract class Connection
{
    /**
     * PDO database worker, agent, aka adapter.
     * @const string
     */
    const AGENT_PDO = 'pdo';

    /**
     * MySQLI database worker, agent, aka adapter.
     * @const string
     */
    const AGENT_MYSQLI = 'mysqli';

    /**
     * Connection connected status.
     * @const integer
     */
    const STATUS_CONNECTED = 1;

    /**
     * Connection disconnected status.
     * @const integer
     */
    const STATUS_DISCONNECTED = 0;

    /**
     * Connection single type.
     * @const string
     */
    const TYPE_SINGLE = 'single';

    /**
     * Connection master type.
     * @const string
     */
    const TYPE_MASTER = 'master';

    /**
     * Connection slave type.
     * @const string
     */
    const TYPE_SLAVE  = 'slave';

    /**
     * Connection type.
     * @var string
     */
    protected $type;

    /**
     * Connection host.
     * @var string
     */
    protected $host;

    /**
     * Connection agent, aka adapter.
     * @var Oppa\Database\Connector\Agent\?
     */
    protected $agent;

    /**
     * Connection agent name, aka adapter name.
     * @var string
     */
    protected $agentName;

    /**
     * Connection configuration.
     * @var array
     */
    protected $configuration = [];

    /**
     * Get connection type.
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Get connection host.
     *
     * @return string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * Get connection agent.
     *
     * @return Oppa\Database\Connector\Agent\?
     */
    public function getAgent() {
        return $this->agent;
    }

    /**
     * Get connection agent name.
     *
     * @return string
     */
    public function getAgentName() {
        return $this->agentName;
    }

    /** Action pattern. */
    abstract public function open();

    /** Action pattern. */
    abstract public function close();

    /** Action pattern. */
    abstract public function status();

    /** Action pattern. */
    abstract protected function attachAgent();

    /** Action pattern. */
    abstract protected function detachAgent();
}
