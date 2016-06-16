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

namespace Oppa\Link;

use Oppa\Config;
use Oppa\Agent;

/**
 * @package    Oppa
 * @subpackage Oppa\Link
 * @object     Oppa\Link\Link
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Link
{
    /**
     * Database agent (aka worker, adapter etc.) names.
     * @const string
     */
    const AGENT_PDO             = 'pdo',
          AGENT_MYSQLI          = 'mysqli';

    /**
     * Link statuses.
     * @const int
     */
    const STATUS_CONNECTED      = 1,
          STATUS_DISCONNECTED   = 0;

    /**
     * Link types.
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
     * @var Oppa\Agent\AgentInterface
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
        $this->type = $type;
        $this->host = $host;
        $this->config = $config;

        // attach agent
        $this->attachAgent();
    }

    /**
     * Destructor.
     */
    final public function __destruct()
    {
        $this->detachAgent();
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
     * @return Oppa\Agent\AgentInterface|null
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
        $this->agent && $this->agent->connect();
    }

    /**
     * Close.
     * @return void
     */
    final public function close()
    {
        $this->agent && $this->agent->disconnect();
    }

    /**
     * Status.
     * @return int   If agent exists.
     * @return false If agent not exists.
     */
    final public function status()
    {
        if ($this->agent) {
            return $this->agent->isConnected()
                ? self::STATUS_CONNECTED : self::STATUS_DISCONNECTED;
        }

        return false;
    }

    /**
     * Attach agent.
     * @return void
     * @throws \RuntimeException
     */
    final private function attachAgent()
    {
        $agentName = strtolower((string) $this->config['agent']);
        switch ($agentName) {
            // for now, only mysqli and if time permits i will add more..
            case self::AGENT_MYSQLI:
                $this->agent = new Agent\Mysqli($this->config);
                $this->agentName = $agentName;
                break;
            default:
                throw new \RuntimeException("Sorry, but '{$agentName}' agent not implemented!");
        }
    }

    /**
     * Detach agent.
     * @return void
     */
    final private function detachAgent()
    {
        $this->agent = null;
        $this->agentName = null;
    }
}
