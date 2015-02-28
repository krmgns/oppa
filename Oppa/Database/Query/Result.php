<?php namespace Oppa\Database\Query;

use \Oppa\Exception\Database as Exception;

abstract class Result
    extends \Oppa\Shablon\Database\Query\Result
{
    const FETCH_OBJECT       = 1;
    const FETCH_ARRAY_ASSOC  = 2;
    const FETCH_ARRAY_NUM    = 3;
    const FETCH_ARRAY_BOTH   = 4;

    protected $result;
    protected $fetchType;

    protected $data = [];

    protected $id = []; // last insert id(s)
    protected $rowsCount = 0;
    protected $rowsAffected = 0;

    public function setFetchType($fetchType) {
        if (is_integer($fetchType)) {
            if (!in_array($fetchType, [1, 2, 3, 4])) {
                throw new Exception\ArgumentException(
                    "Given `{$fetchType}` fetch type is not implemented!");
            }
            $this->fetchType = $fetchType;
        } else {
            $fetchTypeConst = 'self::FETCH_'. strtoupper($fetchType);
            if (!defined($fetchTypeConst)) {
                throw new Exception\ArgumentException(
                    "Given `{$fetchType}` fetch type is not implemented!");
            }
            $this->fetchType = constant($fetchTypeConst);
        }
    }

    public function getFetchType() {
        return $this->fetchType;
    }

    public function getData() {
        return $this->data;
    }

    public function setId($id) {
        $this->id = (array) $id;
    }

    public function getId($all = false) {
        return $all
            ? $this->id       // all insert ids
            : end($this->id); // only last insert id
    }

    public function setRowsCount($count) {
        $this->rowsCount = $count;
    }

    public function getRowsCount() {
        return $this->rowsCount;
    }

    public function setRowsAffected($count) {
        $this->rowsAffected = $count;
    }

    public function getRowsAffected() {
        return $this->rowsAffected;
    }

    public function count() {
        return count($this->data);
    }

    public function getIterator() {
        return new \ArrayIterator($this->data);
    }
}
