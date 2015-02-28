<?php namespace Oppa\Database\Connector;

use \Oppa\Helper;
use \Oppa\Exception\Database as Exception;

final class Connection
    extends \Oppa\Shablon\Database\Connector\Connection
{
    final public function __construct($type, $host, array $configuration) {
        $this->type = $type;
        $this->host = $host;
        $this->configuration = $configuration;
    }

    final public function open() {
        if (!isset($this->agent)) {
            $this->attachAgent();
            $this->agent->connect();
        }
    }

    final public function close() {
        if (isset($this->agent)) {
            $this->agent->disconnect();
            $this->detachAgent();
        }
    }

    final public function status() {
        if (isset($this->agent)) {
            return $this->agent->isConnected()
                ? self::STATUS_CONNECTED : self::STATUS_DISCONNECTED;
        }
        return false;
    }

    final protected function attachAgent() {
        $agentName =@ strtolower($this->configuration['agent']);
        switch ($agentName) {
            case self::AGENT_MYSQLI:
                $this->agent = new Agent\Mysqli($this->configuration);
                $this->agentName = $agentName;
                break;
            default:
                throw new Exception\ValueException(
                    "Sorry, but `{$agentName}` agent not implemented!");
        }
    }

    final protected function detachAgent() {
        $this->agent = null;
        $this->agentName = null;
    }
}
