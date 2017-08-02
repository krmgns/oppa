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

use Oppa\Resource;

/**
 * @package Oppa
 * @object  Oppa\Query\Result\ResultInterface
 * @author  Kerem Güneş <k-gun@mail.com>
 */
interface ResultInterface extends \Countable, \IteratorAggregate
{
    /**
     * Fetch limit.
     * @const int
     */
    public const LIMIT           = 1000; // rows

    /**
     * Fetch types.
     * @const int
     */
    public const AS_OBJECT       = 1, // @default
                 AS_ARRAY_ASC    = 2,
                 AS_ARRAY_NUM    = 3,
                 AS_ARRAY_ASCNUM = 4;

    /**
     * Free.
     * @return void
     */
    public function free(): void;

    /**
     * Reset.
     * @return void
     */
    public function reset(): void;

    /**
     * Process.
     * @param  Oppa\Resource $result
     * @param  int           $limit
     * @param  int|string    $fetchType
     * @return Oppa\Query\Result\ResultInterface
     */
    public function process(Resource $result, int $limit = null, $fetchType = null): ResultInterface;
}
