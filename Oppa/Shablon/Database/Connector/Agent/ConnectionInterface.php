<?php namespace Oppa\Shablon\Database\Connector\Agent;

interface ConnectionInterface
{
    public function connect();
    public function disconnect();
    public function isConnected();
}
