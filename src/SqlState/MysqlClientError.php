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
 * @object  Oppa\SqlState\MysqlClientError
 * @author  Kerem Güneş <k-gun@mail.com>
 * @link    http://dev.mysql.com/doc/refman/5.7/en/error-messages-client.html
 */
abstract /* static */ class MysqlClientError
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
