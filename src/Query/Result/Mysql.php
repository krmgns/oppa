<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *    <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *    <http://www.gnu.org/licenses/gpl-3.0.txt>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Oppa\Query\Result;

use Oppa\Agent;
use Oppa\Exception\InvalidValueException;

/**
 * @package    Oppa
 * @subpackage Oppa\Query\Result
 * @object     Oppa\Query\Result\Mysql
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Mysql extends Result
{
    /**
     * Constructor.
     * @param Oppa\Agent\Mysql $agent
     */
    final public function __construct(Agent\Mysql $agent)
    {
        $this->agent = $agent;
    }

    /**
     * Process.
     * If query action contains "select", then process returned result.
     * If query action contains "update/delete", etc then process affected result.
     * @param  \mysqli_result $result
     * @param  int|null       $limit
     * @param  int|null       $fetchType
     * @return Oppa\Query\Result\ResultInterface
     * @throws Oppa\InvalidValueException
     */
    final public function process($result, int $limit = null, int $fetchType = null): ResultInterface
    {
        $resource = $this->agent->getResource();
        if (!$resource instanceof \mysqli) {
            throw new InvalidValueException('Process resource must be instanceof \mysqli!');
        }

        $i = 0;
        // if result contains result object
        if ($result instanceof \mysqli_result && $result->num_rows > 0) {
            $this->result = $result;

            if ($limit === null) {
                $limit = ResultInterface::LIMIT;
            }

            $fetchType = ($fetchType == null)
                ? $this->fetchType : $this->detectFetchType($fetchType);

            switch ($fetchType) {
                case Result::AS_OBJECT:
                    while ($i < $limit && $row = $this->result->fetch_object()) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case ResultInterface::AS_ARRAY_ASC:
                    while ($i < $limit && $row = $this->result->fetch_assoc()) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case ResultInterface::AS_ARRAY_NUM:
                    while ($i < $limit && $row = $this->result->fetch_array(MYSQLI_NUM)) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case ResultInterface::AS_ARRAY_ASCNUM:
                    while ($i < $limit && $row = $this->result->fetch_array()) {
                        $this->data[$i++] = $row;
                    }
                    break;
                default:
                    $this->free();

                    throw new InvalidValueException(
                        "Could not implement given '{$fetchType}' fetch type!");
            }

            // map result data
            if (isset($this->agent->mapper) && $mapper = $this->agent->getMapper()) {
                $field = $this->result->fetch_field();
                if (isset($field->orgtable)) {
                    $this->data = $mapper->map($field->orgtable, $this->data);
                }
            }
        }

        $this->free();

        // dirty ways to detect last insert id for multiple inserts
        // good point! http://stackoverflow.com/a/15664201/362780
        $id  = $resource->insert_id;
        $ids = $id ? [$id] : [];

        /**
         * // only last id
         * if ($id && $resource->affected_rows > 1) {
         *     $id = ($id + $resource->affected_rows) - 1;
         * }
         *
         * // all ids
         * if ($id && $resource->affected_rows > 1) {
         *     for ($i = 0; $i < $resource->affected_rows - 1; $i++) {
         *         $ids[] = $id + 1;
         *     }
         * }
         */

        // all ids (more tricky)
        if ($id && $resource->affected_rows > 1) {
            $ids = range($id, ($id + $resource->affected_rows) - 1);
        }

        $this->setIds($ids);
        $this->setRowsCount($i);
        $this->setRowsAffected($resource->affected_rows);

        return $this;
    }

    /**
     * Free.
     * @return void
     */
    final public function free(): void
    {
        if ($this->result instanceof \mysqli_result) {
            $this->result->free();
            $this->result = null;
        }
    }
}
