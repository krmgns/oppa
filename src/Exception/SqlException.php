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

namespace Oppa\Exception;

/**
 * @package Oppa
 * @object  Oppa\Exception\SqlException
 * @author  Kerem Güneş <k-gun@mail.com>
 */
class SqlException extends \Exception
{
    /**
     * Sql state.
     * @var ?string
     */
    protected $sqlState;

    /**
     * Constructor.
     * @param ?string     $message
     * @param ?int        $code
     * @param ?string     $sqlState
     * @param ?\Throwable $previous
     */
    public final function __construct(?string $message = '', ?int $code = 0, ?string $sqlState = null,
        ?\Throwable $previous = null)
    {
        // set state
        $this->sqlState = $sqlState;

        // prepend state to message
        if ($this->sqlState) {
            $message = sprintf('SQLSTATE[%s]: %s', $this->sqlState, $message);
        }

        parent::__construct((string) $message, (int) $code, $previous);
    }

    /**
     * Get sql state.
     * @return ?string
     */
    public final function getSqlState(): ?string
    {
        return $this->sqlState;
    }
}
