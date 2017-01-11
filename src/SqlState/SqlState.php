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

namespace Oppa\SqlState;

/**
 * @package    Oppa
 * @subpackage Oppa\SqlState
 * @object     Oppa\SqlState\SqlState
 * @author     Kerem Güneş <k-gun@mail.com>
 * @reflink    https://www.postgresql.org/docs/9.6/static/errcodes-appendix.html
 */
abstract class SqlState
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
