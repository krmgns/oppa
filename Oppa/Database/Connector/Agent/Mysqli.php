<?php namespace Oppa\Database\Connector\Agent;

use \Oppa\Helper;
use \Oppa\Logger;
use \Oppa\Database\Batch;
use \Oppa\Database\Profiler;
use \Oppa\Database\Query\Sql;
use \Oppa\Database\Query\Result;
use \Oppa\Exception\Database as Exception;

final class Mysqli
    extends \Oppa\Shablon\Database\Connector\Agent\Agent
{
    final public function __construct(array $configuration) {
        if (!extension_loaded('mysqli')) {
            throw new \RuntimeException('Mysqli extension is not loaded.');
        }

        $this->batch = new Batch\Mysqli($this);

        $this->result = new Result\Mysqli();
        $this->result->setFetchType(
            isset($configuration['fetch_type'])
                ? $configuration['fetch_type'] : Result::FETCH_OBJECT
        );

        $this->configuration = $configuration;

        if (isset($configuration['query_log']) && $configuration['query_log'] == true) {
            $this->logger = new Logger();
            isset($configuration['query_log_level']) &&
                $this->logger->setLevel($configuration['query_log_level']);
            isset($configuration['query_log_directory']) &&
                $this->logger->setDirectory($configuration['query_log_directory']);
            isset($configuration['query_log_filename_format']) &&
                $this->logger->setFilenameFormat($configuration['query_log_filename_format']);
        }
        if (isset($configuration['profiling']) && $configuration['profiling'] == true) {
            $this->profiler = new Profiler();
        }
    }

    final public function __destruct() {
        $this->disconnect();
    }

    final public function connect() {
        list($host, $name, $username, $password) = [
            $this->configuration['host'],
            $this->configuration['name'],
            $this->configuration['username'],
            $this->configuration['password'],
        ];
        $port = Helper::getArrayValue('port', $this->configuration);
        $socket = Helper::getArrayValue('socket', $this->configuration);

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

        $this->profiler && $this->profiler->start(Profiler::CONNECTION);

        if (!$this->link->real_connect($host, $username, $password, $name, intval($port), $socket)) {
            throw new Exception\ConnectionException(sprintf(
                'Connection error! errno[%d] errmsg[%s]', $this->link->connect_errno, $this->link->connect_error));
        }

        $this->logger && $this->logger->log(Logger::INFO, sprintf(
            'New connection via %s', $_SERVER['REMOTE_ADDR']));

        $this->profiler && $this->profiler->stop(Profiler::CONNECTION);

        if (isset($this->configuration['charset'])) {
            $run = (bool) $this->link->set_charset($this->configuration['charset']);
            if ($run === false) {
                throw new Exception\ErrorException(sprintf(
                    'Failed setting charset as `%s`! errno[%d] errmsg[%s]',
                        $this->configuration['charset'], $this->link->errno, $this->link->error));
            }
        }

        if (isset($this->configuration['timezone'])) {
            $run = (bool) $this->link->query("SET time_zone='{$this->configuration['timezone']}'");
            if ($run === false) {
                throw new Exception\QueryException(sprintf(
                    'Query error! errmsg[%s]', $this->link->error));
            }
        }

        return $this->link;
    }
    final public function disconnect() {
        if ($this->link instanceof \mysqli) {
            $this->link->close();
            $this->link = null;
        }
    }
    final public function isConnected() {
        return ($this->link instanceof \mysqli &&
                $this->link->connect_errno === 0);
    }

    final public function query($query, array $params = null, $limit = null, $fetchType = null) {
        $this->result->reset();

        $query = trim($query);
        if ($query == '') {
            throw new Exception\QueryException('Query cannot be empty!');
        }

        if (!empty($params)) {
            $query = $this->prepare($query, $params);
        }

        $this->logger && $this->logger->log(Logger::INFO, sprintf(
            'New query via %s, query[%s]', $_SERVER['REMOTE_ADDR'], $query));

        if ($this->profiler) {
            $this->profiler->setProperty(Profiler::PROP_QUERY_COUNT);
            $this->profiler->setProperty(Profiler::PROP_LAST_QUERY, $query);
        }

        $this->profiler && $this->profiler->start(Profiler::LAST_QUERY);
        $result = $this->link->query($query);
        $this->profiler && $this->profiler->stop(Profiler::LAST_QUERY);

        if (!$result) {
            try {
                throw new Exception\QueryException(sprintf(
                    'Query error: query[%s], errmsg[%s], errno[%s]',
                        $query, $this->link->error, $this->link->errno
                ), $this->link->errno);
            } catch (Exception\QueryException $e) {
                // log query error
                $this->logger && $this->logger->log(Logger::FAIL, $e->getMessage());
                // check error handler
                $errorHandler = Helper::getArrayValue('query_error_handler', $this->configuration);
                if (is_callable($errorHandler)) {
                    return $errorHandler($e, $query, $params);
                }
                throw $e;
            }
        }

        $this->result->process($this->link, $result, $limit, $fetchType);

        return $this->result;
    }

    final public function get($query, array $params = null, $fetchType = null) {
        return $this->query($query, $params, 1, $fetchType)->getData();
    }

    final public function getAll($query, array $params = null, $fetchType = null) {
        return $this->query($query, $params, null, $fetchType)->getData();
    }

    final public function select($table, array $fields, $where = null, array $params = null, $limit = null) {
        return $this->query(sprintf('SELECT %s FROM %s %s %s',
                $this->escapeIdentifier($fields),
                $this->escapeIdentifier($table),
                $this->where($where, $params),
                $this->limit($limit)
        ))->getData();
    }

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

    final public function delete($table, $where = null, array $params = null, $limit = null) {
        return $this->query(sprintf(
            'DELETE FROM %s %s %s',
                $this->escapeIdentifier($table),
                $this->where($where, $params),
                $this->limit($limit)
        ))->getRowsAffected();
    }

    final public function escape($input, $type = null) {
        // excepting strings, for all formattable types like %d, %f and %F
        if (!is_array($input)) {
            if ($type && $type[0] == '%' && $type != '%s') {
                return sprintf($type, $input);
            }
        }

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

    final public function escapeIdentifier($input) {
        return !is_array($input)
            ? '`'. trim($input, '` ') .'`'
            : join(', ', array_map([$this, 'escapeIdentifier'], $input));
    }

    final public function where($where, array $params = null) {
        if (!empty($params)) {
            $where = 'WHERE '. $this->prepare($where, $params);
        }
        return $where;
    }

    final public function limit($limit) {
        if (is_array($limit)) {
            return sprintf('LIMIT %d, %d', $limit[0], $limit[1]);
        }
        return $limit ? sprintf('LIMIT %d', $limit) : '';
    }
}
