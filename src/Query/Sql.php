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

namespace Oppa\Query;

/**
 * @package Oppa
 * @object  Oppa\Query\Sql
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Sql
{
    // This object is used for only to prevent escaping contents like
    // NOW(), COUNT() etc. in agent.escape() methods. Nothing more..

    /**
     * Keeps raw SQL string.
     * @var string
     */
    protected $query;

    /**
     * Constructor.
     * @param string $query
     */
    public function __construct(string $query)
    {
        $this->query = trim($query);
    }

    /**
     * Stringer.
     * @return string
     */
    public function __toString()
    {
        return $this->query;
    }

    /**
     * To string.
     * @return string
     */
    public function toString(): string
    {
        return $this->query;
    }

    /**
     * New.
     * @param  string $query
     * @return Oppa\Query\Sql
     */
    public static function new(string $query): Sql
    {
        return new Sql($query);
    }
}
