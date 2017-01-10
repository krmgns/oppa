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
 * @object     Oppa\SqlState\MysqlClientError
 * @author     Kerem Güneş <k-gun@mail.com>
 * @reflink    http://dev.mysql.com/doc/refman/5.7/en/error-messages-client.html
 */
abstract class MysqlClientError
{
    public const

        /**
         * OK
         * @const int
         */
        OK = 0,

        /**
         * Codes.
         * @const int
         */
        UNKNOWN_ERROR = 2000,
        SOCKET_CREATE_ERROR = 2001,
        CONNECTION_ERROR = 2002,
        CONN_HOST_ERROR = 2003,
        IPSOCK_ERROR = 2004,
        UNKNOWN_HOST = 2005,
        SERVER_GONE_ERROR = 2006,
        VERSION_ERROR = 2007,
        OUT_OF_MEMORY = 2008,
        WRONG_HOST_INFO = 2009,
        LOCALHOST_CONNECTION = 2010,
        TCP_CONNECTION = 2011,
        SERVER_HANDSHAKE_ERR = 2012,
        SERVER_LOST = 2013,
        COMMANDS_OUT_OF_SYNC = 2014,
        NAMEDPIPE_CONNECTION = 2015,
        NAMEDPIPEWAIT_ERROR = 2016,
        NAMEDPIPEOPEN_ERROR = 2017,
        NAMEDPIPESETSTATE_ERROR = 2018,
        CANT_READ_CHARSET = 2019,
        NET_PACKET_TOO_LARGE = 2020,
        EMBEDDED_CONNECTION = 2021,
        PROBE_SLAVE_STATUS = 2022,
        PROBE_SLAVE_HOSTS = 2023,
        PROBE_SLAVE_CONNECT = 2024,
        PROBE_MASTER_CONNECT = 2025,
        SSL_CONNECTION_ERROR = 2026,
        MALFORMED_PACKET = 2027,
        WRONG_LICENSE = 2028,
        NULL_POINTER = 2029,
        NO_PREPARE_STMT = 2030,
        PARAMS_NOT_BOUND = 2031,
        DATA_TRUNCATED = 2032,
        NO_PARAMETERS_EXISTS = 2033,
        INVALID_PARAMETER_NO = 2034,
        INVALID_BUFFER_USE = 2035,
        UNSUPPORTED_PARAM_TYPE = 2036,
        SHARED_MEMORY_CONNECTION = 2037,
        SHARED_MEMORY_CONNECT_REQUEST_ERROR = 2038,
        SHARED_MEMORY_CONNECT_ANSWER_ERROR = 2039,
        SHARED_MEMORY_CONNECT_FILE_MAP_ERROR = 2040,
        SHARED_MEMORY_CONNECT_MAP_ERROR = 2041,
        SHARED_MEMORY_FILE_MAP_ERROR = 2042,
        SHARED_MEMORY_MAP_ERROR = 2043,
        SHARED_MEMORY_EVENT_ERROR = 2044,
        SHARED_MEMORY_CONNECT_ABANDONED_ERROR = 2045,
        SHARED_MEMORY_CONNECT_SET_ERROR = 2046,
        CONN_UNKNOW_PROTOCOL = 2047,
        INVALID_CONN_HANDLE = 2048,
        SECURE_AUTH = 2049,
        UNUSED_1 = 2049,
        FETCH_CANCELED = 2050,
        NO_DATA = 2051,
        NO_STMT_METADATA = 2052,
        NO_RESULT_SET = 2053,
        NOT_IMPLEMENTED = 2054,
        SERVER_LOST_EXTENDED = 2055,
        STMT_CLOSED = 2056,
        NEW_STMT_METADATA = 2057,
        ALREADY_CONNECTED = 2058,
        AUTH_PLUGIN_CANNOT_LOAD = 2059,
        DUPLICATE_CONNECTION_ATTR = 2060,
        AUTH_PLUGIN_ERR = 2061,
        INSECURE_API_ERR = 2062;
}
