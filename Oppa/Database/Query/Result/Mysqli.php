<?php namespace Oppa\Database\Query\Result;

use \Oppa\Exception\Database as Exception;

final class Mysqli
    extends \Oppa\Database\Query\Result
{

    final public function free() {
        if ($this->result instanceof \mysqli_result) {
            $this->result->free();
            $this->result = null;
        }
    }

    final public function reset() {
        $this->id = [];
        $this->rowsCount = 0;
        $this->rowsAffected = 0;
        $this->data = [];
    }

    final public function process($link, $result, $limit = null, $fetchType = null) {
        if (!$link instanceof \mysqli) {
            throw new Exception\ArgumentException('Process link must be instanceof mysqli!');
        }

        $i = 0;
        if ($result instanceof \mysqli_result && $result->num_rows) {
            if ($limit == null) {
                $limit = PHP_INT_MAX;
            }
            if ($fetchType == null) {
                $fetchType = $this->fetchType;
            }

            $this->result = $result;
            switch ($fetchType) {
                case self::FETCH_OBJECT:
                    while ($i < $limit && $row = $this->result->fetch_object()) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case self::FETCH_ARRAY_ASSOC:
                    while ($i < $limit && $row = $this->result->fetch_assoc()) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case self::FETCH_ARRAY_NUM:
                    while ($i < $limit && $row = $this->result->fetch_array(MYSQLI_NUM)) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case self::FETCH_ARRAY_BOTH:
                    while ($i < $limit && $row = $this->result->fetch_array()) {
                        $this->data[$i++] = $row;
                    }
                    break;
                default:
                    $this->free();
                    throw new Exception\ResultException(
                        "Could not implement given `{$fetchType}` fetch type!");
            }
        }

        $this->free();

        // dirty ways to detect last insert id for multiple inserts
        // good point! http://stackoverflow.com/a/15664201/362780
        $id  = $link->insert_id;
        $ids = [$id];

        /**
         * // only last id
         * if ($id && $link->affected_rows > 1) {
         *     $id = ($id + $link->affected_rows) - 1;
         * }
         *
         * // all ids
         * if ($id && $link->affected_rows > 1) {
         *     for ($i = 0; $i < $link->affected_rows - 1; $i++) {
         *         $ids[] = $id + 1;
         *     }
         * }
         */

        // all ids
        if ($id && $link->affected_rows > 1) {
            $ids = range($id, ($id + $link->affected_rows) - 1);
        }

        // set properties
        $this->setId($ids);
        $this->setRowsCount($i);
        $this->setRowsAffected($link->affected_rows);

        return $this;
    }
}
