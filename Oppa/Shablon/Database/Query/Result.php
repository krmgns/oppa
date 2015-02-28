<?php namespace Oppa\Shablon\Database\Query;

abstract class Result
    implements \IteratorAggregate, \Countable
{
    abstract public function free();
    abstract public function reset();
    abstract public function process($link, $result, $limit = null, $fetchType = null);
}
