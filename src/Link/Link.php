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
use Oppa\Agent\{AgentInterface, Mysql, Pgsql};

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
    public const AGENT_PGSQL           = 'pgsql',
                 AGENT_MYSQL           = 'mysql';

    /**
     * Link statuses.
     * @const int
     */
    public const STATUS_CONNECTED      = 1,
                 STATUS_DISCONNECTED   = 0;

    /**
     * Link types.
     * @const string
     */
    public const TYPE_SINGLE           = 'single',
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
    public function __construct(string $type, string $host, Config $config)
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
    public function __destruct()
    {
        $this->detachAgent();
    }

    /**
     * Get type.
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get host.
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Get agent.
     * @return ?Oppa\Agent\AgentInterface
     */
    public function getAgent(): ?AgentInterface
    {
        return $this->agent;
    }

    /**
     * Get agent name.
     * @return ?string
     */
    public function getAgentName(): ?string
    {
        return $this->agentName;
    }

    /**
     * Get config.
     * @return Oppa\Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Open.
     * @return void
     */
    public function open(): void
    {
        $this->agent->connect();
    }

    /**
     * Close.
     * @return void
     */
    public function close(): void
    {
        $this->agent->disconnect();
    }

    /**
     * Status.
     * @return int  If agent exists.
     * @return null If agent not exists.
     */
    public function status(): ?int
    {
        if ($this->agent != null) {
            return $this->agent->isConnected() ? self::STATUS_CONNECTED : self::STATUS_DISCONNECTED;
        }

        return null;
    }

    /**
     * Attach agent.
     * @return void
     * @throws \RuntimeException
     */
    private function attachAgent(): void
    {
        $this->agentName = strtolower((string) $this->config['agent']);
        switch ($this->agentName) {
            case self::AGENT_MYSQL:
                $this->agent = new Mysql($this->config);
                break;
            case self::AGENT_PGSQL:
                $this->agent = new Pgsql($this->config);
                break;
            default:
                throw new \RuntimeException("Sorry, but '{$this->agentName}' agent not implemented!");
        }
    }

    /**
     * Detach agent.
     * @return void
     */
    private function detachAgent(): void
    {
        $this->agent = null;
        $this->agentName = null;
    }
}
