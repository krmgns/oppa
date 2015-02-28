<?php namespace Oppa\Database\Query;

class Sql
{
    protected $query;

    public function __construct($query) {
        $this->query = trim($query);
    }

    public function toString() {
        return $this->query;
    }
}
