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
 * @object  Oppa\Query\RawSql
 * @author  Kerem Güneş <k-gun@mail.com>
 * @note    Used to prevent escaping contents like NOW(), COUNT() etc. in agent.escape() methods.
 */
abstract class RawSql
{
    /**
     * Contents.
     * @var string
     */
    protected $contents;

    /**
     * Constructor.
     * @param string $contents
     */
    public function __construct(string $contents)
    {
        $this->contents = trim($contents);
    }

    /**
     * String magic.
     * @return string
     */
    public function __toString()
    {
        return $this->contents;
    }

    /**
     * To string.
     * @return string
     */
    public function toString(): string
    {
        return $this->contents;
    }
}
