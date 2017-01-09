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
 * @object     Oppa\Query\Result\Pgsql
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Pgsql extends Result
{
    /**
     * Constructor.
     * @param Oppa\Agent\Pgsql $agent
     */
    final public function __construct(Agent\Pgsql $agent)
    {
        $this->agent = $agent;
    }

    /**
     * Process.
     * If query action contains "select", then process returned result.
     * If query action contains "update/delete" etc, then process affected result.
     * @param  resource $result
     * @param  int|null $limit
     * @param  int|null $fetchType
     * @return Oppa\Query\Result\ResultInterface
     * @throws Oppa\InvalidValueException
     */
    final public function process($result, int $limit = null, int $fetchType = null): ResultInterface
    {
        $resource = $this->agent->getResource();
        if (!is_resource($resource)) {
            throw new InvalidValueException('Process resource must be type of pgsql link!');
        }

        $rowsCount = 0;
        $rowsAffected = 0;
        if (is_resource($result)) {
            $rowsCount = pg_num_rows($result);
            $rowsAffected = pg_affected_rows($result);
        }

        $i = 0;
        // if results
        if ($rowsCount > 0 && pg_result_status($result) === PGSQL_TUPLES_OK) {
            $this->result = $result;

            if ($limit === null) {
                $limit = ResultInterface::LIMIT;
            }

            $fetchType = ($fetchType === null)
                ? $this->fetchType : $this->detectFetchType($fetchType);

            switch ($fetchType) {
                case Result::AS_OBJECT:
                    while ($i < $limit && $row = pg_fetch_object($this->result)) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case ResultInterface::AS_ARRAY_ASC:
                    while ($i < $limit && $row = pg_fetch_assoc($this->result)) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case ResultInterface::AS_ARRAY_NUM:
                    while ($i < $limit && $row = pg_fetch_array($this->result, null, PGSQL_NUM)) {
                        $this->data[$i++] = $row;
                    }
                    break;
                case ResultInterface::AS_ARRAY_ASCNUM:
                    while ($i < $limit && $row = pg_fetch_array($this->result)) {
                        $this->data[$i++] = $row;
                    }
                    break;
                default:
                    $this->free();

                    throw new InvalidValueException("Could not implement given '{$fetchType}' fetch type!");
            }

            // map result data
            if (isset($this->agent->mapper) && $mapper = $this->agent->getMapper()) {
                $fieldTable = pg_field_table($this->result, 0);
                if ($fieldTable) {
                    $this->data = $mapper->map($fieldTable, $this->data);
                }
            }
        }

        $this->free();

        $this->setRowsCount($i);
        $this->setRowsAffected($rowsAffected);

        return $this;
    }

    /**
     * Free.
     * @return void
     */
    final public function free(): void
    {
        if (is_resource($this->result)) {
            pg_free_result($this->result);
            $this->result = null;
        }
    }
}
