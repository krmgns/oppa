<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace Oppa\Link;

use Oppa\{Database, Config};
use Oppa\Agent\{AgentInterface, Mysql, Pgsql};

/**
 * @package Oppa
 * @object  Oppa\Link\Link
 * @author  Kerem Güneş <k-gun@mail.com>
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
     * Link trait.
     * @object Oppa\Link\LinkTrait
     */
    use LinkTrait;

    /**
     * Database.
     * @var Oppa\Database
     */
    protected $database;

    /**
     * Config.
     * @var Oppa\Config
     */
    protected $config;

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
     * Constructor.
     * @param Oppa\Database $database
     * @param Oppa\Config   $config
     * @param string        $type
     * @param string        $host
     */
    public function __construct(Database $database, Config $config, string $type, string $host)
    {
        $this->database = $database;
        $this->config = $config;
        $this->type = $type;
        $this->host = $host;

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
            return $this->agent->isConnected()
                ? self::STATUS_CONNECTED : self::STATUS_DISCONNECTED;
        }

        return null;
    }

    /**
     * Attach agent.
     * @return void
     * @throws Oppa\Link\LinkException
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
                throw new LinkException("Sorry, but '{$this->agentName}' agent not implemented!");
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
