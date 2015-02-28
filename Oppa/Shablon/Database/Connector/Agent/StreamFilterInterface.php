<?php namespace Oppa\Shablon\Database\Connector\Agent;

interface StreamFilterInterface
{
    public function prepare($input, array $params = null);
    public function escape($input, $type = null);
    public function escapeIdentifier($input);
}
