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

namespace Oppa\Database\Connector;

use Oppa\Config;
use Oppa\Database\Agent;

/**
 * @package    Oppa
 * @subpackage Oppa\Database\Connector
 * @object     Oppa\Database\Connector\Connection
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Connection
{
    /**
     * Database agent (aka worker, adapter etc.) names.
     * @const string
     */
    const AGENT_PDO             = 'pdo',
          AGENT_MYSQLI          = 'mysqli';

    /**
     * Connection statuses.
     * @const int
     */
    const STATUS_CONNECTED      = 1,
          STATUS_DISCONNECTED   = 0;

    /**
     * Connection types.
     * @const string
     */
    const TYPE_SINGLE           = 'single',
          TYPE_MASTER           = 'master',
          TYPE_SLAVE            = 'slave';

    /**
     * Type.
     * @var string
     */
    protected $type;

    /**
     * Host.
     * @var string
     */
    protected $host;

    /**
     * Agent.
     * @var Oppa\Database\Agent\AgentInterface
     */
    protected $agent;

    /**
     * Agent name.
     * @var string
     */
    protected $agentName;

    /**
     * Config.
     * @var Oppa\Config
     */
    protected $config;

    /**
     * Constructor.
     * @param string $type
     * @param string $host
     * @param array  $config
     */
    final public function __construct(string $type, string $host, Config $config)
    {
        $this->type   = $type;
        $this->host   = $host;
        $this->config = $config;
    }

    /**
     * Get type.
     * @return string
     */
    final public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get host.
     * @return string
     */
    final public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Get agent.
     * @return Oppa\Database\Agent\AgentInterface|null
     */
    final public function getAgent()
    {
        return $this->agent;
    }

    /**
     * Get agent name.
     * @return string|null
     */
    final public function getAgentName()
    {
        return $this->agentName;
    }

    /**
     * Get config.
     * @return Oppa\Config
     */
    final public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Open.
     * @return void
     */
    final public function open()
    {
        if ($this->agent == null) {
            // attach agent first
            $this->attachAgent();
            // and open connection
            $this->agent->connect();
        }
    }

    /**
     * Close.
     * @return void
     */
    final public function close()
    {
        if ($this->agent != null) {
            // close connection first
            $this->agent->disconnect();
            // and detach agent
            $this->detachAgent();
        }
    }

    /**
     * Check status.
     * @return int    If agent is exists.
     * @return false  If agent does not exists.
     */
    final public function status()
    {
        if (isset($this->agent)) {
            return $this->agent->isConnected()
                ? self::STATUS_CONNECTED : self::STATUS_DISCONNECTED;
        }

        return false;
    }

    /**
     * Attach agent.
     * @return void
     * @throws \Exception
     */
    final protected function attachAgent()
    {
        $agentName = strtolower((string) $this->config['agent']);
        switch ($agentName) {
            // for now, only mysqli
            // if time permits, i will extend..
            case self::AGENT_MYSQLI:
                $this->agent = new Agent\Mysqli($this->config);
                $this->agentName = $agentName;
                break;
            default:
                throw new \Exception("Sorry, but `{$agentName}` agent not implemented!");
        }
    }

    /**
     * Detach agent.
     * @return void
     */
    final protected function detachAgent()
    {
        $this->agent = null;
        $this->agentName = null;
    }
}
