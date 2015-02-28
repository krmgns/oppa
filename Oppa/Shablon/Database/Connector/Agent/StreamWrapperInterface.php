<?php namespace Oppa\Shablon\Database\Connector\Agent;

interface StreamWrapperInterface
{
    public function query($query, array $params = null);

    public function get($query, array $params = null, $fetchType = null);
    public function getAll($query, array $params = null, $fetchType = null);

    public function select($table, array $fields, $where = null, array $params = null, $limit = null);
    public function insert($table, array $data);
    public function update($table, array $data, $where = null, array $params = null, $limit = null);
    public function delete($table, $where = null, array $params = null, $limit = null);

    public function id(); // uuid, guid, serial, sequence, identity, last_insert_id WTF!
    public function rowsCount();
    public function rowsAffected();
}
