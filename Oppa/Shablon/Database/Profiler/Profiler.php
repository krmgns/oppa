<?php namespace Oppa\Shablon\Database\Profiler;

use \Oppa\Exception;

abstract class Profiler
{
    const CONNECTION  = 1;
    const TRANSACTION = 2;
    const LAST_QUERY  = 3;

    const PROP_QUERY_COUNT = 10;
    const PROP_LAST_QUERY  = 11;

    protected $profiles = [];
    protected $properties = [];

    public function getProfile($name) {
        if (isset($this->profiles[$name])) {
            return $this->profiles[$name];
        }

        throw new Exception\ErrorException("Could not find a `{$name}` name to profile.");
    }
    public function getProfileAll() {
        return $this->profiles;
    }

    abstract public function start($name);
    abstract public function stop($name);
    abstract public function setProperty($name, $value = null);
    abstract public function getProperty($name);
}
