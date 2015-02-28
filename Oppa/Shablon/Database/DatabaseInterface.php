<?php namespace Oppa\Shablon\Database;

interface DatabaseInterface
{
    public function connect($host = null);
    public function disconnect($host = null);
    public function isConnected($host = null);
    public function getConnection($host = null);
    public function info();
}
