<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace Oppa\Query\Result;

use Oppa\{Agent, Resource};
use Oppa\Exception\{InvalidResourceException, InvalidValueException};

/**
 * @package Oppa
 * @object  Oppa\Query\Result\Mysql
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Mysql extends Result
{
    /**
     * Constructor.
     * @param Oppa\Agent\Mysql $agent
     */
    public function __construct(Agent\Mysql $agent)
    {
        $this->agent = $agent;
    }

    /**
     * Process.
     * If query action contains "select", then process returned result.
     * If query action contains "update/delete" etc, then process affected result.
     * @param  Oppa\Resource $result
     * @param  int           $limit
     * @param  int|string    $fetchType
     * @return Oppa\Query\Result\ResultInterface
     * @throws Oppa\Exception\InvalidResourceException, Oppa\Exception\InvalidValueException
     */
    public function process(Resource $result, int $limit = null, $fetchType = null): ResultInterface
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
