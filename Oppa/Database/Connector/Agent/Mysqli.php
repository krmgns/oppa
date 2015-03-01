<?php
/**
 * Copyright (c) 2015 Kerem Gunes
 *    <http://qeremy.com>
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

namespace Oppa\Database\Connector\Agent;

use \Oppa\Helper;
use \Oppa\Logger;
use \Oppa\Database\Batch;
use \Oppa\Database\Profiler;
use \Oppa\Database\Query\Sql;
use \Oppa\Database\Query\Result;
use \Oppa\Exception\Database as Exception;

/**
 * @package    Oppa
 * @subpackage Oppa\Database\Connector\Agent
 * @object     Oppa\Database\Connector\Agent\Agent
 * @uses       Oppa\Helper,
               Oppa\Logger,
               Oppa\Database\Batch,
               Oppa\Database\Profiler,
               Oppa\Database\Query\Sql,
               Oppa\Database\Query\Result,
               Oppa\Exception\Database
 * @extends    Oppa\Shablon\Database\Connector\Agent\Agent
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
final class Mysqli
    extends \Oppa\Shablon\Database\Connector\Agent\Agent
{
    /**
     * Create a fresh mysql agent using mysqli extensions.
     *
     * @param  array $configuration
     * @throws \RuntimeException
     */
    final public function __construct(array $configuration) {
        // we need it
        if (!extension_loaded('mysqli')) {
            throw new \RuntimeException('Mysqli extension is not loaded.');
        }

        // assign configuration
        $this->configuration = $configuration;

        // assign transaction object
        $this->batch = new Batch\Mysqli($this);

        // assign result object
        $this->result = new Result\Mysqli();
        $this->result->setFetchType(
            isset($configuration['fetch_type'])
                ? $configuration['fetch_type'] : Result::FETCH_OBJECT
        );

        // assign logger if config'ed
        if (isset($configuration['query_log']) && $configuration['query_log'] == true) {
            $this->logger = new Logger();
            isset($configuration['query_log_level']) &&
                $this->logger->setLevel($configuration['query_log_level']);
            isset($configuration['query_log_directory']) &&
                $this->logger->setDirectory($configuration['query_log_directory']);
            isset($configuration['query_log_filename_format']) &&
                $this->logger->setFilenameFormat($configuration['query_log_filename_format']);
        }

        // assign profiler if config'ed
        if (isset($configuration['profiling']) && $configuration['profiling'] == true) {
            $this->profiler = new Profiler();
        }
    }

    /**
     * Unplug itself before saying goodbye to world.
     *
     * @return void
     */
    final public function __destruct() {
        $this->disconnect();
    }

    /**
     * Open a connection with given options.
     *
     * @throws Oppa\Exception\Database\?
     * @return object|resource
     */
    final public function connect() {
        // export credentials
        list($host, $name, $username, $password) = [
            $this->configuration['host'], $this->configuration['name'],
            $this->configuration['username'], $this->configuration['password'],
        ];
        // get port/socket options
        $port = Helper::getArrayValue('port', $this->configuration);
        $socket = Helper::getArrayValue('socket', $this->configuration);

        // call big boss
        $this->link = mysqli_init();

        // supported constants: http://php.net/mysqli.real_connect
        if (isset($this->configuration['connect_options'])) {
            foreach ($this->configuration['connect_options'] as $option => $value) {
                if (!is_string($option)) {
                    throw new Exception\ArgumentException(
                        'Please set all connection option constant names as string to track any setting error!');
                }
                $option = strtoupper($option);
                if (!defined($option)) {
                    throw new Exception\ArgumentException("`{$option}` option constant is not defined!");
                }
                if (!$this->link->options(constant($option), $value)) {
                    throw new Exception\ErrorException("Setting {$option} option failed!");
                }
            }
        }

        // start connection profiling
        $this->profiler && $this->profiler->start(Profiler::CONNECTION);

        if (!$this->link->real_connect($host, $username, $password, $name, intval($port), $socket)) {
            throw new Exception\ConnectionException(sprintf(
                'Connection error! errno[%d] errmsg[%s]', $this->link->connect_errno, $this->link->connect_error));
        }

        // finish connection profiling
        $this->profiler && $this->profiler->stop(Profiler::CONNECTION);

        // log with info level
        $this->logger && $this->logger->log(Logger::INFO,
            sprintf('New connection via %s', $_SERVER['REMOTE_ADDR']));

        // set charset for connection
        if (isset($this->configuration['charset'])) {
            $run = (bool) $this->link->set_charset($this->configuration['charset']);
            if ($run === false) {
                throw new Exception\ErrorException(sprintf(
                    'Failed setting charset as `%s`! errno[%d] errmsg[%s]',
                        $this->configuration['charset'], $this->link->errno, $this->link->error));
            }
        }

        // set timezone for connection
        if (isset($this->configuration['timezone'])) {
            $run = (bool) $this->link->query("SET time_zone='{$this->configuration['timezone']}'");
            if ($run === false) {
                throw new Exception\QueryException(sprintf(
                    'Query error! errmsg[%s]', $this->link->error));
            }
        }

        return $this->link;
    }

    /**
     * Close a connection.
     *
     * @return void
     */
    final public function disconnect() {
        // time to say goodbye baby
        if ($this->link instanceof \mysqli) {
            $this->link->close();
            $this->link = null;
        }
    }

    /**
     * Check connection.
     *
     * @return boolean
     */
    final public function isConnected() {
        return ($this->link instanceof \mysqli && $this->link->connect_errno === 0);
    }

    /**
     * Yes, "Query" of the S(Q)L...
     *
     * @param  string     $query     Raw SQL query.
     * @param  array|null $params    Prapering params.
     * @param  integer    $limit     Generally used in internal methods.
     * @param  integer    $fetchType That will overwrite on Result.fetchType.
     * @throws Oppa\Exception\Database\?
     * @return Oppa\Database\Query\Result
     */
    final public function query($query, array $params = null, $limit = null, $fetchType = null) {
        // reset result vars
        $this->result->reset();

        // trim query
        $query = trim($query);
        if ($query == '') {
            throw new Exception\QueryException('Query cannot be empty!');
        }

        // prepare if any params
        if (!empty($params)) {
            $query = $this->prepare($query, $params);
        }

        // log query with info level
        $this->logger && $this->logger->log(Logger::INFO, sprintf(
            'New query via %s, query[%s]', $_SERVER['REMOTE_ADDR'], $query));

        // add query count, last query
        if ($this->profiler) {
            $this->profiler->setProperty(Profiler::PROP_QUERY_COUNT);
            $this->profiler->setProperty(Profiler::PROP_LAST_QUERY, $query);
        }

        // start last query profiling
        $this->profiler && $this->profiler->start(Profiler::LAST_QUERY);

        // go go go..
        $result = $this->link->query($query);

        // finish last query profiling
        $this->profiler && $this->profiler->stop(Profiler::LAST_QUERY);

        // i always loved to handle errors
        if (!$result) {
            try {
                throw new Exception\QueryException(sprintf(
                    'Query error: query[%s], errmsg[%s], errno[%s]',
                        $query, $this->link->error, $this->link->errno
                ), $this->link->errno);
            } catch (Exception\QueryException $e) {
                // log query error with fail level
                $this->logger && $this->logger->log(Logger::FAIL, $e->getMessage());

                // check error handler
                $errorHandler = Helper::getArrayValue('query_error_handler', $this->configuration);
                // if user has error handler, return using it
                if (is_callable($errorHandler)) {
                    return $errorHandler($e, $query, $params);
                }

                // throw it!
                throw $e;
            }
        }

        // send query result to Result object to process and return it
        return $this->result->process($this->link, $result, $limit, $fetchType);
    }

    /**
     * Select actions only one row.
     *
     * @param  string     $query
     * @param  array|null $params
     * @param  integer    $fetchType
     * @return Oppa\Database\Query\Result
     */
    final public function get($query, array $params = null, $fetchType = null) {
        return $this->query($query, $params, 1, $fetchType)->getData();
    }

    /**
     * Select actions all rows.
     *
     * @param  string     $query
     * @param  array|null $params
     * @param  integer    $fetchType
     * @return Oppa\Database\Query\Result
     */
    final public function getAll($query, array $params = null, $fetchType = null) {
        return $this->query($query, $params, null, $fetchType)->getData();
    }

    /**
     * Select actions all rows.
     *
     * @param  string     $table
     * @param  array      $fields
     * @param  string     $where
     * @param  array      $params
     * @param  integer    $fetchType
     * @return Oppa\Database\Query\Result
     */
    final public function select($table, array $fields = ['*'], $where = null, array $params = null, $limit = null,
        $fetchType = null
    ) {
        return $this->query(sprintf('SELECT %s FROM %s %s %s',
                $this->escapeIdentifier($fields),
                $this->escapeIdentifier($table),
                $this->where($where, $params),
                $this->limit($limit)
        ), null, null, $fetchType)->getData();
    }

    /**
     * Insert actions.
     *
     * @param  string $table
     * @param  array  $data
     * @return integer|null
     */
    final public function insert($table, array $data) {
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
     * Insert actions.
     *
     * @param  string  $table
     * @param  array   $data
     * @param  string  $where
     * @param  array   $params
     * @param  integer $limit
     * @return integer
     */
    final public function update($table, array $data, $where = null, array $params = null, $limit = null) {
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
     * Insert actions.
     *
     * @param  string  $table
     * @param  string  $where
     * @param  array   $params
     * @param  integer $limit
     * @return integer
     */
    final public function delete($table, $where = null, array $params = null, $limit = null) {
        return $this->query(sprintf(
            'DELETE FROM %s %s %s',
                $this->escapeIdentifier($table),
                $this->where($where, $params),
                $this->limit($limit)
        ))->getRowsAffected();
    }

    /**
     * Escape given input.
     *
     * @param  string $input
     * @param  string $type
     * @throws Oppa\Exception\Database\ArgumentException
     * @return string
     */
    final public function escape($input, $type = null) {
        // excepting strings, for all formattable types like %d, %f and %F
        if (!is_array($input)) {
            if ($type && $type[0] == '%' && $type != '%s') {
                return sprintf($type, $input);
            }
        }

        // no escape raws sql inputs like NOW(), ROUND(total) etc.
        if ($input instanceof Sql) {
            return $input->toString();
        }

        switch (gettype($input)) {
            case 'NULL'   : return 'NULL';
            case 'integer': return $input;
            // 1/0, afaik true/false not supported yet in mysql
            case 'boolean': return (int) $input;
            // %F = non-locale aware
            case 'double' : return sprintf('%F', $input);
            // in/not in statements
            case 'array'  : return join(', ', array_map([$this, 'escape'], $input));
            // i trust you baby..
            case 'string' : return "'". $this->link->real_escape_string($input) ."'";
            default:
                throw new Exception\ArgumentException(sprintf(
                    'Unimplemented type encountered! type: `%s`', gettype($input)));
        }

        return $input;
    }

    /**
     * Escape identifier like table name, field name.
     *
     * @param  string $input
     * @return string
     */
    final public function escapeIdentifier($input) {
        if ($input == '*') {
            return $input;
        }

        return !is_array($input)
            ? '`'. trim($input, '` ') .'`'
            : join(', ', array_map([$this, 'escapeIdentifier'], $input));
    }

    /**
     * Prepare "WHERE" statement.
     *
     * @param  string     $where
     * @param  array|null $params
     * @return string
     */
    final public function where($where, array $params = null) {
        if (!empty($params)) {
            $where = 'WHERE '. $this->prepare($where, $params);
        }

        return $where;
    }

    /**
     * Prepare "LIMIT" statement.
     *
     * @param  integer $limit
     * @return string
     */
    final public function limit($limit) {
        if (is_array($limit)) {
            return sprintf('LIMIT %d, %d', $limit[0], $limit[1]);
        }

        return $limit ? sprintf('LIMIT %d', $limit) : '';
    }
}
