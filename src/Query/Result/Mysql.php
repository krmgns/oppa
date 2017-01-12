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

use Oppa\{Agent, Resource};
use Oppa\Exception\{InvalidValueException, InvalidResourceException};

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
     * @param  Oppa\Resource $result
     * @param  int           $limit
     * @param  int|string    $fetchType
     * @return Oppa\Query\Result\ResultInterface
     * @throws Oppa\Exception\InvalidResourceException
     */
    final public function process(Resource $result, int $limit = null, $fetchType = null): ResultInterface
    {
        $resource = $this->agent->getResource();
        if ($resource->getType() != Resource::TYPE_MYSQL_LINK) {
            throw new InvalidResourceException('Process resource must be instanceof \mysqli!');
        }

        $resourceObject = $resource->getObject();
        $resultObject = $result->getObject();

        $rowsCount = 0;
        $rowsAffected = $resourceObject->affected_rows;
        if ($result->getType() == Resource::TYPE_MYSQL_RESULT) {
            $rowsCount = $resultObject->num_rows;
        }

        $i = 0;
        // if results
        if ($rowsCount > 0) {
            $this->result = $result;

            if ($limit === null) {
                $limit = $this->fetchLimit;
            } elseif ($limit === -1) {
                $limit = ResultInterface::LIMIT;
            }

            $fetchType = ($fetchType === null)
                ? $this->fetchType : $this->detectFetchType($fetchType);

            switch ($fetchType) {
                case Result::AS_OBJECT:
                    while ($i < $limit && $row = $resultObject->fetch_object($this->fetchObject)) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case ResultInterface::AS_ARRAY_ASC:
                    while ($i < $limit && $row = $resultObject->fetch_assoc()) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case ResultInterface::AS_ARRAY_NUM:
                    while ($i < $limit && $row = $resultObject->fetch_array(MYSQLI_NUM)) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case ResultInterface::AS_ARRAY_ASCNUM:
                    while ($i < $limit && $row = $resultObject->fetch_array()) {
                        $this->data[$i++] = $row;
                    }
                    break;
                default:
                    $this->free();

                    throw new InvalidValueException("Could not implement given '{$fetchType}' fetch type!");
            }

            // map result data
            if (isset($this->agent->mapper) && $mapper = $this->agent->getMapper()) {
                $field = $resultObject->fetch_field();
                if (isset($field->orgtable)) {
                    $this->data = $mapper->map($field->orgtable, $this->data);
                }
            }
        }

        $this->free();

        $this->setRowsCount($i);
        $this->setRowsAffected($rowsAffected);

        // last insert id
        $id = (int) $resourceObject->insert_id;
        if ($id) {
            $ids = [$id];

            // dirty ways to detect last insert id for multiple inserts
            // good point! http://stackoverflow.com/a/15664201/362780

            // only last id
            // if ($rowsAffected > 1) {
            //     $id = ($id + $rowsAffected) - 1;
            // }

            // all ids
            // if ($rowsAffected > 1) {
            //     for ($i = 0; $i < $rowsAffected - 1; $i++) {
            //         $ids[] = $id + 1;
            //     }
            // }

            // all ids (more tricky?)
            if ($rowsAffected > 1) {
                $ids = range($id, ($id + $rowsAffected) - 1);
            }

            $this->setIds($ids);
        }

        return $this;
    }
}
