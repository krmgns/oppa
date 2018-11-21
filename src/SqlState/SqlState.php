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

namespace Oppa\SqlState;

/**
 * @package Oppa
 * @object  Oppa\SqlState\SqlState
 * @author  Kerem Güneş <k-gun@mail.com>
 * @link    https://www.postgresql.org/docs/9.6/static/errcodes-appendix.html
 */
abstract /* static */ class SqlState
{
    public const
        /**
         * Oppa states.
         * @const string
         */
        OPPA_CONNECTION_ERROR = 'OPPA0',
        OPPA_HOST_ERROR = 'OPPA1',
        OPPA_DATABASE_ERROR = 'OPPA2',
        OPPA_AUTHENTICATION_ERROR = 'OPPA3',
        OPPA_CHARSET_ERROR = 'OPPA4',
        OPPA_TIMEZONE_ERROR = 'OPPA5',
        OPPA_QUERY_ERROR = 'OPPA6';
}
