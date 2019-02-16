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

use Oppa\Resource;

/**
 * @package Oppa
 * @object  Oppa\Query\Result\ResultInterface
 * @author  Kerem Güneş <k-gun@mail.com>
 */
interface ResultInterface extends \Countable, \IteratorAggregate, \ArrayAccess
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
