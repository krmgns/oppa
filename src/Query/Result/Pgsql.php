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
use Oppa\Exception\{InvalidResourceException, InvalidValueException};

/**
 * @package Oppa
 * @object  Oppa\Query\Result\Pgsql
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Pgsql extends Result
{
    /**
     * Constructor.
     * @param Oppa\Agent\Pgsql $agent
     */
    public function __construct(Agent\Pgsql $agent)
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
     * @param  string        $query @internal
     * @return Oppa\Query\Result\ResultInterface
     * @throws Oppa\Exception\InvalidResourceException, Oppa\Exception\InvalidValueException
     */
    public function process(Resource $result, int $limit = null, $fetchType = null,
        string $query = null): ResultInterface
    {
        $resource = $this->agent->getResource();
        if ($resource->getType() != Resource::TYPE_PGSQL_LINK) {
            throw new InvalidResourceException('Process resource must be type of pgsql link!');
        }

        $resourceObject = $resource->getObject();
        $resultObject = $result->getObject();

        $rowsCount = 0;
        $rowsAffected = 0;
        if ($result->getType() == Resource::TYPE_PGSQL_RESULT) {
            $rowsCount = pg_num_rows($resultObject);
            $rowsAffected = pg_affected_rows($resultObject);
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
                    while ($i < $limit && $row = pg_fetch_object($resultObject, null, $this->fetchObject)) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case ResultInterface::AS_ARRAY_ASC:
                    while ($i < $limit && $row = pg_fetch_assoc($resultObject)) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case ResultInterface::AS_ARRAY_NUM:
                    while ($i < $limit && $row = pg_fetch_array($resultObject, null, PGSQL_NUM)) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case ResultInterface::AS_ARRAY_ASCNUM:
                    while ($i < $limit && $row = pg_fetch_array($resultObject)) {
                        $this->data[$i++] = $row;
                    }
                    break;
                default:
                    $this->free();

                    throw new InvalidValueException("Could not implement given '{$fetchType}' fetch type!");
            }

            // map result data
            if (isset($this->agent->mapper) && $mapper = $this->agent->getMapper()) {
                $fieldTable = pg_field_table($resultObject, 0);
                if ($fieldTable) {
                    $this->data = $mapper->map($fieldTable, $this->data);
                }
            }
        }

        $this->free();

        $this->setRowsCount($i);
        $this->setRowsAffected($rowsAffected);

        // last insert id
        if ($query && stripos($query, 'INSERT') === 0) {
            $result = pg_query($resourceObject, 'SELECT lastval() AS id');
            if ($result) {
                $id = (int) pg_fetch_result($result, 'id');
                if ($id) {
                    $ids = [$id];
                    // multiple inserts
                    if ($rowsAffected > 1) {
                        $ids = range(($id - $rowsAffected) + 1, $id);
                    }
                    $this->setIds($ids);
                }
                pg_free_result($result);
            }
        }

        return $this;
    }
}
