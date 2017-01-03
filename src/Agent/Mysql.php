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

use Oppa\Query\{Sql, Result};
use Oppa\{Util, Config, Logger, Mapper, Profiler, Batch,
    SqlState\Mysql as SqlState, SqlState\MysqlError as SqlStateError};
use Oppa\Exception\{Error, QueryException, ConnectionException, InvalidValueException, InvalidConfigException};

/**
 * @package    Oppa
 * @subpackage Oppa\Agent
 * @object     Oppa\Agent\Mysql
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Mysql extends Agent
{
    /**
     * Constructor.
     * @param  Oppa\Config $config
     * @throws \RuntimeException
     */
    final public function __construct(Config $config)
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
        $this->result->setFetchType($this->config['fetch_type'] ?? Result\Result::AS_OBJECT);

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
     * @return \mysqli
     * @throws Oppa\Exception\InvalidValueException, Oppa\Exception\Error
     */
    final public function connect(): \mysqli
    {
        // no need to get excited
        if ($this->isConnected()) {
            return $this->resource;
        }

        // export credentials & others
        [$host, $name, $username, $password] = [
            $this->config['host'], $this->config['name'],
            $this->config['username'], $this->config['password'],
        ];
        $port = (int) $this->config['port'];
        $socket = (string) $this->config['socket'];

        // call big boss
        $this->resource = mysqli_init();

        // supported constants: http://php.net/mysqli.options
        if (isset($this->config['options'])) {
            foreach ($this->config['options'] as $option => $value) {
                if (!$this->resource->options($option, $value)) {
                    throw new Error("Setting '{$option}' option failed!");
                }
            }
        }

        // start connection profiling
        $this->profiler && $this->profiler->start(Profiler::CONNECTION);

        if (!$this->resource->real_connect($host, $username, $password, $name, $port, $socket)) {
            throw new ConnectionException($this->resource->connect_error, $this->resource->connect_errno, SqlState::ER_YES);
        }

        // finish connection profiling
        $this->profiler && $this->profiler->stop(Profiler::CONNECTION);

        // log with info level
        $this->logger && $this->logger->log(Logger::INFO, sprintf('New connection via %s addr.', Util::getIp()));

        // set charset for connection
        if (isset($this->config['charset'])) {
            $run = (bool) $this->resource->set_charset($this->config['charset']);
            if ($run === false) {
                throw new QueryException($this->resource->error, $this->resource->errno, $this->resource->sqlstate);
            }
        }

        // set timezone for connection
        if (isset($this->config['timezone'])) {
            $run = (bool) $this->resource->query($this->prepare('SET `time_zone` = ?', [$this->config['timezone']]));
            if ($run === false) {
                throw new QueryException($this->resource->error, $this->resource->errno, $this->resource->sqlstate);
            }
        }

        // fill mapper map for once
        if ($this->mapper) {
            try {
                $result = $this->query("SELECT table_name, column_name, data_type, is_nullable, numeric_precision, column_type
                    FROM information_schema.columns WHERE table_schema = '{$name}'");
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
            } catch (QueryException $e) {}
        }

        return $this->resource;
    }

    /**
     * Disconnect.
     * @return void
     */
    final public function disconnect(): void
    {
        if ($this->resource instanceof \mysqli) {
            $this->resource->close();
            $this->resource = null;
        }
    }

    /**
     * Check connection.
     * @return bool
     */
    final public function isConnected(): bool
    {
        return ($this->resource instanceof \mysqli && $this->resource->connect_errno === SqlStateError::OK);
    }

    /**
     * Yes, "Query" of the S(Q)L...
     * @param  string    $query     Raw SQL query.
     * @param  array     $params    Prepare params.
     * @param  int|array $limit     Generally used in internal methods.
     * @param  int       $fetchType By-pass Result::fetchType.
     * @return Oppa\Query\Result\ResultInterface
     * @throws Oppa\Exception\InvalidValueException, Oppa\QueryException
     */
    final public function query(string $query, array $params = null, $limit = null,
        $fetchType = null): Result\ResultInterface
    {
        // reset result vars
        $this->result->reset();

        $query = trim($query);
        if ($query == '') {
            throw new InvalidValueException('Query cannot be empty!');
        }

        if (!empty($params)) {
            $query = $this->prepare($query, $params);
        }

        // log query with info level
        $this->logger && $this->logger->log(Logger::INFO, sprintf(
            'New query [%s] via %s addr.', $query, Util::getIp()));

        // increase query count, add last query profiler
        if ($this->profiler) {
            $this->profiler->addQuery($query);
        }

        // start last query profiling
        $this->profiler && $this->profiler->start(Profiler::QUERY);

        // go go go!
        $result = $this->resource->query($query);

        // finish last query profiling
        $this->profiler && $this->profiler->stop(Profiler::QUERY);

        if ($result === false) {
            try {
                throw new QueryException($this->resource->error, $this->resource->errno, $this->resource->sqlstate);
            } catch (QueryException $e) {
                // log query error with fail level
                $this->logger && $this->logger->log(Logger::FAIL, $e->getMessage());

                // check user error handler
                $errorHandler = $this->config['query_error_handler'];
                if ($errorHandler && is_callable($errorHandler)) {
                    $errorHandler($e, $query, $params);

                    // no throw
                    return $this->result;
                }

                throw $e;
            }
        }

        return $this->result->process($result, $limit, $fetchType);
    }

    /**
     * Select.
     * @param  string       $table
     * @param  string|array $fields
     * @param  string       $where
     * @param  array        $params
     * @param  int|array    $limit
     * @param  int          $fetchType
     * @return any
     */
    final public function select(string $table, $fields = null, string $where = null,
        array $params = null, $limit = null, int $fetchType = null)
    {
        if (empty($fields)) {
            $fields = '*';
        }

        return $this->query(sprintf(
            'SELECT %s FROM %s %s %s',
                $this->escapeIdentifier($fields),
                $this->escapeIdentifier($table),
                $this->where($where, $params),
                $this->limit($limit)
        ), null, null, $fetchType)->getData();
    }

    /**
     * Select one.
     * @param  string       $table
     * @param  string|array $fields
     * @param  string       $where
     * @param  array        $params
     * @param  int          $fetchType
     * @return any
     */
    final public function selectOne(string $table, $fields = null, string $where = null,
        array $params = null, int $fetchType = null)
    {
        return $this->select($table, $fields, $where, $params, 1, $fetchType)[0] ?? null;
    }

    /**
     * Insert.
     * @param  string $table
     * @param  array  $data
     * @return ?int
     */
    final public function insert(string $table, array $data): ?int
    {
        // simply check is not assoc to prepare multi-insert
        if (!isset($data[0])) {
            $data = [$data];
        }

        $keys = array_keys(current($data));
        $values = [];
        foreach ($data as $dat) {
            $values[] = '('. $this->escape(array_values($dat)) .')';
        }

        return $this->query(sprintf(
            'INSERT INTO %s (%s) VALUES %s',
                $this->escapeIdentifier($table),
                $this->escapeIdentifier($keys),
                join(',', $values)
        ))->getId();
    }

    /**
     * Update.
     * @param  string    $table
     * @param  array     $data
     * @param  string    $where
     * @param  array     $params
     * @param  int|array $limit
     * @return int
     */
    final public function update(string $table, array $data, string $where = null,
        array $params = null, $limit = null): int
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = sprintf('%s = %s',
                $this->escapeIdentifier($key), $this->escape($value));
        }

        return $this->query(sprintf(
            'UPDATE %s SET %s %s %s',
                $this->escapeIdentifier($table),
                join(', ', $set),
                $this->where($where, $params),
                $this->limit($limit)
        ))->getRowsAffected();
    }

    /**
     * Delete.
     * @param  string    $table
     * @param  string    $where
     * @param  array     $params
     * @param  int|array $limit
     * @return int
     */
    final public function delete(string $table, string $where = null,
        array $params = null, $limit = null): int
    {
        return $this->query(sprintf(
            'DELETE FROM %s %s %s',
                $this->escapeIdentifier($table),
                $this->where($where, $params),
                $this->limit($limit)
        ))->getRowsAffected();
    }

    /**
     * Count.
     * @param  string $query
     * @return int
     */
    final public function count(string $query): int
    {
        $result = $this->get("SELECT count(*) AS count FROM ({$query}) AS tmp");

        return intval($result->count);
    }

    /**
     * Escape.
     * @param  any    $input
     * @param  string $type
     * @return any
     * @throws Oppa\Exception\InvalidValueException
     */
    final public function escape($input, string $type = null)
    {
        $inputType = gettype($input);

        // escape strings %s and for all formattable types like %d, %f and %F
        if ($inputType != 'array' && $type && $type[0] == '%') {
            if ($type != '%s') {
                return sprintf($type, $input);
            } else {
                return $this->escapeString((string) $input);
            }
        }

        switch ($inputType) {
            case 'string':
                return $this->escapeString($input);
            case 'NULL':
                return 'NULL';
            case 'integer':
                return $input;
            case 'boolean':
                return (int) $input;
            case 'double':
                return sprintf('%F', $input); // %F = non-locale aware
            case 'array':
                return join(', ', array_map([$this, 'escape'], $input)); // in/not in statements
            default:
                // no escape raws sql inputs like NOW(), ROUND(total) etc.
                if ($input instanceof Sql) {
                    return $input->toString();
                }
                throw new InvalidValueException(sprintf("Unimplemented '{$inputType}' type encountered!"));
        }

        return $input;
    }

    /**
     * Escape string.
     * @param  string $input
     * @param  bool   $quote
     * @return string
     */
    final public function escapeString(string $input, bool $quote = true): string
    {
        $input = $this->resource->real_escape_string($input);
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
    final public function escapeIdentifier($input): string
    {
        if ($input == '*') {
            return $input;
        }

        if (is_array($input)) {
            return join(', ', array_map([$this, 'escapeIdentifier'], $input));
        }

        return '`'. trim($input, ' `') .'`';
    }

    /**
     * Prepare "WHERE" statement.
     * @param  string $where
     * @param  array  $params
     * @return ?string
     */
    final public function where(string $where = null, array $params = null): ?string
    {
        if (!empty($params)) {
            $where = 'WHERE '. $this->prepare($where, $params);
        }

        return $where;
    }

    /**
     * Prepare "LIMIT" statement.
     * @param  array|int $limit
     * @return string
     */
    final public function limit($limit): string
    {
        if (is_array($limit)) {
            return isset($limit[0], $limit[1])
                ? sprintf('LIMIT %d, %d', $limit[0], $limit[1])
                : sprintf('LIMIT %d', $limit[0]);
        }

        return $limit ? 'LIMIT '. $limit : '';
    }
}
