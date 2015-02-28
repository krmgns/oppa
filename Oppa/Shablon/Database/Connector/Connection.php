<?php namespace Oppa\Shablon\Database\Connector;

abstract class Connection
{
    const AGENT_PDO = 'pdo';
    const AGENT_MYSQLI = 'mysqli';

    const STATUS_CONNECTED = 1;
    const STATUS_DISCONNECTED = 0;

    const TYPE_SINGLE = 'single';
    const TYPE_MASTER = 'master';
    const TYPE_SLAVE  = 'slave';

    protected $type, $host, $agent, $agentName;
    protected $configuration = [];

    public function getType() {
        return $this->type;
    }

    public function getHost() {
        return $this->host;
    }

    public function getAgent() {
        return $this->agent;
    }

    public function getAgentName() {
        return $this->agentName;
    }

    abstract public function open();
    abstract public function close();
    abstract public function status();

    abstract protected function attachAgent();
    abstract protected function detachAgent();
}
