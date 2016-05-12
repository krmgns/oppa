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

namespace Oppa\Database\Agent;

use Oppa\Config;
use Oppa\Mapper;
use Oppa\Logger;
use Oppa\Database\Batch;
use Oppa\Database\Profiler;
use Oppa\Database\Query\Sql;
use Oppa\Database\Query\Result;

/**
 * @package    Oppa
 * @subpackage Oppa\Database\Agent
 * @object     Oppa\Database\Agent\Mysqli
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Mysqli extends Agent
{
    /**
     * Constructor.
     * @param  Oppa\Config $config
     * @throws \Exception
     */
    final public function __construct(Config $config)
    {
        // we need it like crazy..
        if (!extension_loaded('mysqli')) {
            throw new \Exception('Mysqli extension is not loaded.');
        }

        // assign config
        $this->config = $config;

        // assign batch object (for transaction)
        $this->batch = new Batch\Mysqli($this);

        // assign data mapper
        $mapping = $this->config['map_result'];
        if ($mapping === true) {
            $this->mapper = new Mapper([
                'tiny2bool' => (bool) $this->config['map_result_tiny2bool'],
            ]);
        }

        // assign result object
        $this->result = new Result\Mysqli($this);
        $this->result->setFetchType(
            isset($this->config['fetch_type'])
                ? $this->config['fetch_type'] : Result::FETCH_OBJECT
        );

        // assign logger if config'ed
        if ($this->config['query_log'] == true) {
            $this->logger = new Logger();
            isset($this->config['query_log_level']) &&
                $this->logger->setLevel($this->config['query_log_level']);
            isset($this->config['query_log_directory']) &&
                $this->logger->setDirectory($this->config['query_log_directory']);
        }

        // assign profiler if config'ed
        if ($this->config['profile'] == true) {
            $this->profiler = new Profiler();
        }
    }

    /**
     * Unplug itself before saying goodbye to world.
     * @return void
     */
    final public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Connect.
     * @return \mysqli
     * @throws \Exception
     */
    final public function connect(): \mysqli
    {
        // no need to get excited
        if ($this->isConnected()) {
            return $this->link;
        }

        // export credentials
        list($host, $name, $username, $password) = [
            $this->config['host'], $this->config['name'],
            $this->config['username'], $this->config['password'],
        ];
        // get port/socket options if provided
        $port = (int) $this->config['port'];
        $socket = (string) $this->config['socket'];

        // call big boss
        $this->link = mysqli_init();

        // supported constants: http://php.net/mysqli.real_connect
        if (isset($this->config['connect_options'])) {
            foreach ($this->config['connect_options'] as $option => $value) {
                if (!is_string($option)) {
                    throw new \Exception(
                        'Please set all connection option constant names as '.
                        'string to track any setting error!');
                }
                $option = strtoupper($option);
                if (!defined($option)) {
                    throw new \Exception("`{$option}` option constant is not defined!");
                }
                if (!$this->link->options(constant($option), $value)) {
                    throw new \Exception("Setting `{$option}` option failed!");
                }
            }
        }

        // start connection profiling
        $this->profiler && $this->profiler->start(Profiler::CONNECTION);

        if (!$this->link->real_connect($host, $username, $password, $name, $port, $socket)) {
            throw new \Exception(sprintf(
                'Connection error! errno[%d] errmsg[%s]',
                    $this->link->connect_errno, $this->link->connect_error));
        }

        // finish connection profiling
        $this->profiler && $this->profiler->stop(Profiler::CONNECTION);

        // log with info level
        $this->logger && $this->logger->log(Logger::INFO, sprintf(
            'New connection via %s', $_SERVER['REMOTE_ADDR']));

        // set charset for connection
        if (isset($this->config['charset'])) {
            $run = (bool) $this->link->set_charset($this->config['charset']);
            if ($run === false) {
                throw new \Exception(sprintf(
                    'Failed setting charset as `%s`! errno[%d] errmsg[%s]',
                        $this->config['charset'], $this->link->errno, $this->link->error));
            }
        }

        // set timezone for connection
        if (isset($this->config['timezone'])) {
            $run = (bool) $this->link->query($this->prepare(
                "SET `time_zone` = ?", [$this->config['timezone']]));
            if ($run === false) {
                throw new \Exception(sprintf('Query error! errmsg[%s]', $this->link->error));
            }
        }

        // fill mapper map for once
        if ($this->mapper) {
            $result = null;
            try {
                // get table columns info
                $this->query('SELECT * FROM `information_schema`.`columns` WHERE `table_schema` = %s', [$name]);
                if ($this->result->count()) {
                    $map = [];
                    foreach ($this->result as $result) {
                        $length = null;
                        if (// detect length for integers (actually, used for only tiny2bool action)
                            substr($result->DATA_TYPE, -3) == 'int' ||
                            // detect length for strings (actually, not in use for now)
                            substr($result->DATA_TYPE, -4) == 'char'
                        ) {
                            $length =@ sscanf($result->COLUMN_TYPE, "{$result->DATA_TYPE}(%d)%s")[0];
                        }
                        // needed only these for now
                        $map[$result->TABLE_NAME][$result->COLUMN_NAME]['type'] = $result->DATA_TYPE;
                        $map[$result->TABLE_NAME][$result->COLUMN_NAME]['length'] = $length;
                        $map[$result->TABLE_NAME][$result->COLUMN_NAME]['nullable'] = ($result->IS_NULLABLE == 'YES');
                    }
                    // free result
                    $this->result->reset();
                    // set mapper map
                    $this->mapper->setMap($map);
                }
            } catch (\Throwable $e) {}
        }

        return $this->link;
    }

    /**
     * Disconnect.
     * @return void
     */
    final public function disconnect()
    {
        // time to say goodbye baby
        if ($this->link instanceof \mysqli) {
            $this->link->close();
            $this->link = null;
        }
    }

    /**
     * Check connection.
     * @return bool
     */
    final public function isConnected(): bool
    {
        return ($this->link instanceof \mysqli && $this->link->connect_errno === 0);
    }

    /**
     * Yes, "Query" of the S(Q)L...
     * @param  string    $query     Raw SQL query.
     * @param  array     $params    Prepare params.
     * @param  int|array $limit     Generally used in internal methods.
     * @param  int       $fetchType By-pass Result::fetchType.
     * @return Oppa\Database\Query\Result
     * @throws \Exception
     */
    final public function query(string $query, array $params = null, $limit = null, $fetchType = null)
    {
        // reset result vars
        $this->result->reset();

        // trim query
        $query = trim($query);
        if ($query == '') {
            throw new \Exception('Query cannot be empty!');
        }

        // prepare if any params
        if (!empty($params)) {
            $query = $this->prepare($query, $params);
        }

        // log query with info level
        $this->logger && $this->logger->log(Logger::INFO, sprintf('New query: [%s]', $query));

        // increase query count, set last query
        if ($this->profiler) {
            $this->profiler->addQuery($query);
        }

        // start last query profiling
        $this->profiler && $this->profiler->start(Profiler::QUERY);

        // go go go..
        $result = $this->link->query($query);

        // finish last query profiling
        $this->profiler && $this->profiler->stop(Profiler::QUERY);

        if ($result === false) {
            try {
                throw new \Exception(sprintf('Query error: query[%s], errno[%s], errmsg[%s]',
                    $query, $this->link->errno, $this->link->error
                ), $this->link->errno);
            } catch (\Exception $e) {
                // log query error with fail level
                $this->logger && $this->logger->log(Logger::FAIL, $e->getMessage());

                // check error handler
                $errorHandler = $this->config['query_error_handler'];
                // if user has error handler, return using it
                if ($errorHandler && is_callable($errorHandler)) {
                    return $errorHandler($e, $query, $params);
                }

                throw $e;
            }
        }

        // send query result to Result object to process and return it
        return $this->result->process($this->link, $result, $limit, $fetchType);
    }

    /**
     * Get.
     * @param  string $query
     * @param  array  $params
     * @param  int    $fetchType
     * @return object|array|null
     */
    final public function get(string $query, array $params = null, int $fetchType = null)
    {
        return $this->query($query, $params, 1, $fetchType)->getData(0);
    }

    /**
     * Get all.
     * @param string $query
     * @param array  $params
     * @param int    $fetchType
     * @return array
     */
    final public function getAll(string $query, array $params = null, int $fetchType = null): array
    {
        return $this->query($query, $params, null, $fetchType)->getData();
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
        $this->select($table, $fields, $where, $params, 1, $fetchType)[0] ?? null;
    }

    /**
     * Insert.
     * @param  string $table
     * @param  array  $data
     * @return int|null
     */
    final public function insert(string $table, array $data)
    {
        // simply check is not assoc to prepare multi-insert
        if (!isset($data[0])) {
            $data = [$data];
        }

        $keys = array_keys(current($data));
        $values = [];
        foreach ($data as $d) {
            $values[] = '('. $this->escape(array_values($d)) .')';
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
     * @return string
     * @throws \Exception
     */
    final public function escape($input, string $type = null): string
    {
        // escape strings %s and for all formattable types like %d, %f and %F
        if (!is_array($input) && $type && $type[0] == '%') {
            if ($type != '%s') {
                return sprintf($type, $input);
            } else {
                return "'". $this->link->real_escape_string($input) ."'";
            }
        }

        // no escape raws sql inputs like NOW(), ROUND(total) etc.
        if ($input instanceof Sql) {
            return $input->toString();
        }

        switch (gettype($input)) {
            case 'NULL':
                return 'NULL';
            case 'integer':
                return $input;
            // 1/0, afaik true/false not supported yet in mysql
            case 'boolean':
                return (int) $input;
            // %F = non-locale aware
            case 'double':
                return sprintf('%F', $input);
            // in/not in statements
            case 'array':
                return join(', ', array_map([$this, 'escape'], $input));
            // i trust you baby..
            case 'string':
                return "'". $this->link->real_escape_string($input) ."'";
            default:
                throw new \Exception(sprintf('Unimplemented type encountered! type: `%s`', gettype($input)));
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
     * @return string
     */
    final public function where(string $where, array $params = null): string
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
