<?php namespace Oppa;

use \Oppa\Configuration;
use \Oppa\Database\Connector\Connector;

final class Database
    implements \Oppa\Shablon\Database\DatabaseInterface
{
    private $info;
    private $connector;

    final public function __construct(Configuration $configuration) {
        $this->connector = new Connector($configuration);
    }

    final public function connect($host = null) {
        return $this->connector->connect($host);
    }
    final public function disconnect($host = null) {
        return $this->connector->disconnect($host);
    }

    final public function isConnected($host = null) {
        return $this->connector->isConnected($host);
    }

    final public function getConnection($host = null) {
        return $this->connector->getConnection($host);
    }

    // @notimplement
    final public function info() {}
}
