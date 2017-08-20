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

namespace Oppa\Agent;

use Oppa\SqlState\SqlState;
use Oppa\Query\{Sql, Result};
use Oppa\{Util, Config, Logger, Mapper, Profiler, Batch, Resource};
use Oppa\Exception\{QueryException, ConnectionException,
    InvalidValueException, InvalidConfigException, InvalidQueryException, InvalidResourceException};

/**
 * @package Oppa
 * @object  Oppa\Agent\Mysql
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Mysql extends Agent
{
    /**
     * Constructor.
     * @param  Oppa\Config $config
     * @throws \RuntimeException
     */
    public function __construct(Config $config)
    {
        // we need it like a crazy..
        if (!extension_loaded('mysqli')) {
            throw new \RuntimeException('MySQLi extension is not loaded!');
        }

        $this->config = $config;

        // assign batch object (for transactions)
        $this->batch = new Batch\Mysql($this);

        // assign data mapper
        if ($this->config['map_result']) {
            $this->mapper = new Mapper();
            if (isset($this->config['map_result_bool'])) {
                $this->mapper->setMapOptions(['bool' => (bool) $this->config['map_result_bool']]);
            }
        }

        // assign result object
        $this->result = new Result\Mysql($this);
        isset($this->config['fetch_type']) &&
            $this->result->setFetchType($this->config['fetch_type']);
        isset($this->config['fetch_limit']) &&
            $this->result->setFetchLimit($this->config['fetch_limit']);

        // assign logger if config'ed
        if ($this->config['query_log']) {
            $this->logger = new Logger();
            isset($this->config['query_log_level']) &&
                $this->logger->setLevel($this->config['query_log_level']);
            isset($this->config['query_log_directory']) &&
                $this->logger->setDirectory($this->config['query_log_directory']);
        }

        // assign profiler if config'ed
        if ($this->config['profile']) {
            $this->profiler = new Profiler();
        }
    }

    /**
     * Connect.
     * @return void
     * @throws Oppa\Exception\ConnectionException
     */
    public function connect(): void
    {
        // no need to get excited
        if ($this->isConnected()) {
            return;
        }

        // export credentials & others
        [$host, $name, $username, $password] = [
            $this->config['host'], $this->config['name'],
            $this->config['username'], $this->config['password'],
        ];
        $port = (int) $this->config['port'];
        $socket = (string) $this->config['socket'];

        // call big boss
        $resource = mysqli_init();

        // supported constants: http://php.net/mysqli.options
        if (isset($this->config['options'])) {
            foreach ($this->config['options'] as $option => $value) {
                if (!$resource->options($option, $value)) {
                    throw new ConnectionException("Setting '{$option}' option failed!");
                }
            }
        }

        // start connection profile
        $this->profiler && $this->profiler->start(Profiler::CONNECTION);

        $resourceStatus =@ $resource->real_connect($host, $username, $password, $name, $port, $socket);
        if (!$resourceStatus) {
            $error = $this->parseConnectionError();
            throw new ConnectionException($error['message'], $error['code'], $error['sql_state']);
        }

        // finish connection profile
        $this->profiler && $this->profiler->stop(Profiler::CONNECTION);

        // log with info level
        $this->logger && $this->logger->log(Logger::INFO, sprintf('New connection via %s addr.', Util::getIp()));

        // assign resource
        $this->resource = new Resource($resource);

        // set charset for connection
        if (isset($this->config['charset'])) {
            $run = $resource->set_charset($this->config['charset']);
            if (!$run) {
                throw new ConnectionException(sprintf('Unable to connect to MySQL server at "%s", '.
                    'invalid or not-supported character set "%s" given!', $this->config['host'], $this->config['charset']),
                        $resource->errno, SqlState::OPPA_CHARSET_ERROR);
            }
        }

        // set timezone for connection
        if (isset($this->config['timezone'])) {
            $run = $resource->query($this->prepare('SET time_zone = ?', [$this->config['timezone']]));
            if (!$run) {
                throw new ConnectionException(sprintf('Unable to connect to MySQL server at "%s", '.
                    'invalid or not-supported timezone "%s" given.', $this->config['host'], $this->config['timezone']),
                        $resource->errno, SqlState::OPPA_TIMEZONE_ERROR);
            }
        }

        // fill mapper map for once
        if ($this->mapper) {
            try {
                $result = $this->query("SELECT table_name, column_name, data_type, is_nullable, numeric_precision, column_type
                    FROM information_schema.columns WHERE table_schema = ?", [$name], -1, 1);
                if ($result->count()) {
                    $map = [];
                    foreach ($result->getData() as $data) {
                        $length = null;
                        // detect length (used for only bool's)
                        if ($data->data_type == Mapper::DATA_TYPE_BIT) {
                            $length = (int) $data->numeric_precision;
                        } elseif (substr($data->data_type, -3) == Mapper::DATA_TYPE_INT) {
                            $length = sscanf($data->column_type, $data->data_type .'(%d)')[0] ?? null;
                        }
                        $map[$data->table_name][$data->column_name]['type'] = $data->data_type;
                        $map[$data->table_name][$data->column_name]['length'] = $length;
                        $map[$data->table_name][$data->column_name]['nullable'] = ($data->is_nullable == 'YES');
                    }
                    $this->mapper->setMap($map);
                }
                $result->reset();
            } catch (QueryException $e) {
                throw new ConnectionException('Could not retrieve schema info for mapper!',
                    null, SqlState::OPPA_CONNECTION_ERROR, $e);
            }
        }
    }

    /**
     * Disconnect.
     * @return void
     */
    public function disconnect(): void
    {
        $this->resource && $this->resource->close();
    }

    /**
     * Check connection.
     * @return bool
     */
    public function isConnected(): bool
    {
        return ($this->resource && $this->resource->getObject()->connect_errno === 0);
    }

    /**
     * Yes, "Query" of the S(Q)L...
     * @param  string     $query
     * @param  array      $queryParams
     * @param  int|array  $limit     Generally used in internal methods.
     * @param  int|string $fetchType By-pass Result::fetchType.
     * @return Oppa\Query\Result\ResultInterface
     * @throws Oppa\Exception\{InvalidQueryException, InvalidResourceException, QueryException}
     */
    public function query(string $query, array $queryParams = null, $limit = null,
        $fetchType = null): Result\ResultInterface
    {
        // reset result
        $this->result->reset();

        $query = trim($query);
        if ($query == '') {
            throw new InvalidQueryException('Query cannot be empty!');
        }

        $resource = $this->resource->getObject();
        if (!$resource) {
            throw new InvalidResourceException('No valid connection resource to make a query!');
        }

        $query = $this->prepare($query, $queryParams);

        // log query with info level
        $this->logger && $this->logger->log(Logger::INFO,
            sprintf('New query [%s] via %s addr.', $query, Util::getIp()));

        // increase query count, add last query profiler
        if ($this->profiler) {
            $this->profiler->addQuery($query);
        }

        // query & query profile
        $this->profiler && $this->profiler->start(Profiler::QUERY);
        $result = $resource->query($query);
        $this->profiler && $this->profiler->stop(Profiler::QUERY);

        if (!$result) {
            $error = $this->parseQueryError($query);
            try {
                throw new QueryException($error['message'], $error['code'], $error['sql_state']);
            } catch (QueryException $e) {
                // log query error with fail level
                $this->logger && $this->logger->log(Logger::FAIL, $e->getMessage());

                // check user error handler
                $errorHandler = $this->config['query_error_handler'];
                if ($errorHandler && is_callable($errorHandler)) {
                    $errorHandler($e, $query, $queryParams);

                    // no throw
                    return $this->result;
                }

                throw $e;
            }
        }

        return $this->result->process(new Resource($result), $limit, $fetchType);
    }

    /**
     * Count.
     * @param  string $table
     * @param  string $where
     * @param  array  $whereParams
     * @return ?int
     */
    public function count(string $table, string $where = null, array $whereParams = null): ?int
    {
        $query = $this->prepare('SELECT count(*) AS count FROM %n %v',
            [$table, $this->where($where, $whereParams)]);

        $result = (array) $this->get($query);

        return isset($result['count']) ? intval($result['count']) : null;
    }

    /**
     * Escape.
     * @param  any    $input
     * @param  string $inputFormat
     * @return any
     * @throws Oppa\Exception\InvalidValueException
     */
    public function escape($input, string $inputFormat = null)
    {
        $inputType = gettype($input);

        // in/not in statements
        if ($inputType == 'array') {
            return join(', ', array_map([$this, 'escape'], $input));
        }

        // escape strings %s and for all formattable types like %d, %f and %F
        if ($inputFormat && $inputFormat[0] == '%') {
            if ($inputFormat == '%s') {
                return $this->escapeString((string) $input);
            }
            return sprintf($inputFormat, $input);
        }

        switch ($inputType) {
            case 'NULL':
                return 'NULL';
            case 'string':
                return $this->escapeString($input);
            case 'integer':
                return $input;
            case 'boolean':
                return (int) $input; // 1/0, afaik true/false not supported yet in mysql
            case 'double':
                return sprintf('%F', $input); // %F = non-locale aware
            default:
                // no escape raws sql inputs like NOW(), ROUND(total) etc.
                if ($input instanceof Sql) {
                    return $input->toString();
                }

                throw new InvalidValueException("Unimplemented '{$inputType}' type encountered!");
        }

        return $input;
    }

    /**
     * Escape string.
     * @param  string $input
     * @param  bool   $quote
     * @return string
     */
    public function escapeString(string $input, bool $quote = true): string
    {
        $input = $this->resource->getObject()->real_escape_string($input);
        if ($quote) {
            $input = "'{$input}'";
        }

        return $input;
    }

    /**
     * Escape identifier.
     * @param  string|array $input
     * @return string
     */
    public function escapeIdentifier($input): string
    {
        if ($input == '*') {
            return $input;
        }

        if (is_array($input)) {
            return join(', ', array_map([$this, 'escapeIdentifier'], $input));
        }

        if (is_string($input) && strpos($input, '.')) {
            return join('.', array_map([$this, 'escapeIdentifier'], explode('.', $input)));
        }

        return '`'. trim($input, '`') .'`';
    }

    /**
     * Parse connection error.
     * @return array
     */
    private function parseConnectionError(): array
    {
        $return = ['message' => 'Unknown error.', 'code' => null, 'sql_state' => null];
        if ($error = error_get_last()) {
            $errorMessage = preg_replace('~mysqli::real_connect\(\): +\(.+\): +~', '', $error['message']);
            preg_match('~\((?<sql_state>[0-9A-Z]+)/(?<code>\d+)\)~', $error['message'], $match);
            if (isset($match['sql_state'], $match['code'])) {
                $return['sql_state'] = $match['sql_state'];
                switch ($match['code']) {
                    case '2002':
                        $return['message'] = sprintf('Unable to connect to MySQL server at "%s", '.
                            'could not translate host name "%s" to address.', $this->config['host'], $this->config['host']);
                        $return['sql_state'] = SqlState::OPPA_HOST_ERROR;
                        break;
                    case '1044':
                        $return['message'] = sprintf('Unable to connect to MySQL server at "%s", '.
                            'database "%s" does not exist.', $this->config['host'], $this->config['name']);
                        $return['sql_state'] = SqlState::OPPA_DATABASE_ERROR;
                        break;
                    case '1045':
                        $return['message'] = sprintf('Unable to connect to MySQL server at "%s", '.
                            'password authentication failed for user "%s".', $this->config['host'], $this->config['username']);
                        $return['sql_state'] = SqlState::OPPA_AUTHENTICATION_ERROR;
                        break;
                    default:
                        $return['message'] = $errorMessage .'.';
                }
            } else {
                $return['message'] = $errorMessage .'.';
                $return['sql_state'] = SqlState::OPPA_CONNECTION_ERROR;
            }
        }

        return $return;
    }

    /**
     * Parse query error.
     * @param  string $query
     * @return array
     */
    private function parseQueryError(string $query): array
    {
        $return = ['message' => 'Unknown error.', 'code' => null, 'sql_state' => null];
        $resource = $this->resource->getObject();
        if ($resource->errno) {
            $return['code'] = $resource->errno;
            $return['sql_state'] = $resource->sqlstate;
            // dump useless verbose message
            if ($resource->sqlstate == '42000') {
                preg_match('~syntax to use near (?<query>.+) at line (?<line>\d+)~sm', $resource->error, $match);
                if (isset($match['query'], $match['line'])) {
                    $return['message'] = sprintf('Syntax error at or near "%s", line %d. Query: "%s".',
                        trim(substr($match['query'], 1, -1)), $match['line'], $query);
                }
            } else {
                $return['message'] = $resource->error;
            }
        }

        return $return;
    }
}
