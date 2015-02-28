<?php namespace Oppa\Shablon\Database\Connector;

use \Oppa\Database\Connector\Connection;

abstract class Connector
{
    protected $configuration;
    protected $connections = [];

    abstract public function connect($host = null);
    abstract public function disconnect($host = null);
    abstract public function isConnected($host = null);

    abstract public function setConnection($host, Connection $connection);
    abstract public function getConnection($host = null);
}
