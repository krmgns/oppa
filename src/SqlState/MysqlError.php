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
 * @object     Oppa\SqlState\MysqlError
 * @author     Kerem Güneş <k-gun@mail.com>
 * @reflink    http://dev.mysql.com/doc/refman/5.7/en/error-messages-client.html
 */
abstract class MysqlError
{
    /**
     * OK
     * @const int
     */
    public const OK = 0;

    /**
     * States.
     * @const string
     */
    public const CR_UNKNOWN_ERROR = 2000;
    public const CR_SOCKET_CREATE_ERROR = 2001;
    public const CR_CONNECTION_ERROR = 2002;
    public const CR_CONN_HOST_ERROR = 2003;
    public const CR_IPSOCK_ERROR = 2004;
    public const CR_UNKNOWN_HOST = 2005;
    public const CR_SERVER_GONE_ERROR = 2006;
    public const CR_VERSION_ERROR = 2007;
    public const CR_OUT_OF_MEMORY = 2008;
    public const CR_WRONG_HOST_INFO = 2009;
    public const CR_LOCALHOST_CONNECTION = 2010;
    public const CR_TCP_CONNECTION = 2011;
    public const CR_SERVER_HANDSHAKE_ERR = 2012;
    public const CR_SERVER_LOST = 2013;
    public const CR_COMMANDS_OUT_OF_SYNC = 2014;
    public const CR_NAMEDPIPE_CONNECTION = 2015;
    public const CR_NAMEDPIPEWAIT_ERROR = 2016;
    public const CR_NAMEDPIPEOPEN_ERROR = 2017;
    public const CR_NAMEDPIPESETSTATE_ERROR = 2018;
    public const CR_CANT_READ_CHARSET = 2019;
    public const CR_NET_PACKET_TOO_LARGE = 2020;
    public const CR_EMBEDDED_CONNECTION = 2021;
    public const CR_PROBE_SLAVE_STATUS = 2022;
    public const CR_PROBE_SLAVE_HOSTS = 2023;
    public const CR_PROBE_SLAVE_CONNECT = 2024;
    public const CR_PROBE_MASTER_CONNECT = 2025;
    public const CR_SSL_CONNECTION_ERROR = 2026;
    public const CR_MALFORMED_PACKET = 2027;
    public const CR_WRONG_LICENSE = 2028;
    public const CR_NULL_POINTER = 2029;
    public const CR_NO_PREPARE_STMT = 2030;
    public const CR_PARAMS_NOT_BOUND = 2031;
    public const CR_DATA_TRUNCATED = 2032;
    public const CR_NO_PARAMETERS_EXISTS = 2033;
    public const CR_INVALID_PARAMETER_NO = 2034;
    public const CR_INVALID_BUFFER_USE = 2035;
    public const CR_UNSUPPORTED_PARAM_TYPE = 2036;
    public const CR_SHARED_MEMORY_CONNECTION = 2037;
    public const CR_SHARED_MEMORY_CONNECT_REQUEST_ERROR = 2038;
    public const CR_SHARED_MEMORY_CONNECT_ANSWER_ERROR = 2039;
    public const CR_SHARED_MEMORY_CONNECT_FILE_MAP_ERROR = 2040;
    public const CR_SHARED_MEMORY_CONNECT_MAP_ERROR = 2041;
    public const CR_SHARED_MEMORY_FILE_MAP_ERROR = 2042;
    public const CR_SHARED_MEMORY_MAP_ERROR = 2043;
    public const CR_SHARED_MEMORY_EVENT_ERROR = 2044;
    public const CR_SHARED_MEMORY_CONNECT_ABANDONED_ERROR = 2045;
    public const CR_SHARED_MEMORY_CONNECT_SET_ERROR = 2046;
    public const CR_CONN_UNKNOW_PROTOCOL = 2047;
    public const CR_INVALID_CONN_HANDLE = 2048;
    public const CR_SECURE_AUTH = 2049;
    public const CR_UNUSED_1 = 2049;
    public const CR_FETCH_CANCELED = 2050;
    public const CR_NO_DATA = 2051;
    public const CR_NO_STMT_METADATA = 2052;
    public const CR_NO_RESULT_SET = 2053;
    public const CR_NOT_IMPLEMENTED = 2054;
    public const CR_SERVER_LOST_EXTENDED = 2055;
    public const CR_STMT_CLOSED = 2056;
    public const CR_NEW_STMT_METADATA = 2057;
    public const CR_ALREADY_CONNECTED = 2058;
    public const CR_AUTH_PLUGIN_CANNOT_LOAD = 2059;
    public const CR_DUPLICATE_CONNECTION_ATTR = 2060;
    public const CR_AUTH_PLUGIN_ERR = 2061;
    public const CR_INSECURE_API_ERR = 2062;
}
