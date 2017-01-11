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
 * @package    Oppa
 * @subpackage Oppa\Agent
 * @object     Oppa\Agent\Pgsql
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Pgsql extends Agent
{
    /**
     * Constructor.
     * @param  Oppa\Config $config
     * @throws \RuntimeException
     */
    final public function __construct(Config $config)
    {
        // we need it like a crazy..
        if (!extension_loaded('pgsql')) {
            throw new \RuntimeException('PgSQL extension is not loaded!');
        }

        $this->config = $config;

        // assign batch object (for transactions)
        $this->batch = new Batch\Pgsql($this);

        // assign data mapper
        if ($this->config['map_result']) {
            $this->mapper = new Mapper();
            if (isset($this->config['map_result_bool'])) {
                $this->mapper->setMapOptions(['bool' => (bool) $this->config['map_result_bool']]);
            }
        }

        // assign result object
        $this->result = new Result\Pgsql($this);
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
     * @return void
     * @throws Oppa\Exception\ConnectionException
     */
    final public function connect(): void
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

        // prepare connection string
        $connectionString = sprintf("host=%s dbname=%s", $host, $name);
        if ($port)     $connectionString .= " port={$port}";
        if ($username) $connectionString .= " user={$username}";
        if ($password) $connectionString .= " password='". addcslashes($password, "'\\") ."'";
        $options = $this->config['options'] ?? [];
        if (null !== ($opt = Util::arrayPick($options, 'connect_timeout'))) {
            $connectionString .= " connect_timeout={$opt}";
        }
        if ($opt = Util::arrayPick($options, 'sslmode')) {
            $connectionString .= " sslmode={$opt}";
        }
        if ($opt = Util::arrayPick($options, 'service')) {
            $connectionString .= " service={$opt}";
        }
        if ($opt = $this->config['timezone']) $connectionStringOptions[] = "--timezone=\'{$opt}\'";
        if ($opt = $this->config['charset'])  $connectionStringOptions[] = "--client_encoding=\'{$opt}\'";
        if (isset($connectionStringOptions)) {
            $connectionString .= " options='". join(' ', $connectionStringOptions) ."'";
        }

        // start connection profile
        $this->profiler && $this->profiler->start(Profiler::CONNECTION);

        $resource =@ pg_connect($connectionString);
        $resourceStatus = pg_connection_status($resource);
        if ($resourceStatus === false || $resourceStatus === PGSQL_CONNECTION_BAD) {
            $resource =@ pg_connect($connectionString, PGSQL_CONNECT_FORCE_NEW); // re-try
            $resourceStatus = pg_connection_status($resource);
        }

        if (!$resource || !($resourceStatus === PGSQL_CONNECTION_OK)) {
            $error = $this->parseConnectionError();
            throw new ConnectionException($error['message'], $error['code'], $error['sql_state']);
        }

        // finish connection profile
        $this->profiler && $this->profiler->stop(Profiler::CONNECTION);

        // log with info level
        $this->logger && $this->logger->log(Logger::INFO, sprintf('New connection via %s addr.', Util::getIp()));

        // assign resource
        $this->resource = new Resource($resource);

        // fill mapper map for once
        if ($this->mapper) {
            try {
                $result = $this->query("SELECT table_name, column_name, data_type, is_nullable, character_maximum_length
                    FROM information_schema.columns WHERE table_schema = 'public'");
                if ($result->count()) {
                    $map = [];
                    foreach ($result->getData() as $data) {
                        $length = null;
                        // detect length (used for only bool's)
                        if ($data->data_type == Mapper::DATA_TYPE_BIT) {
                            $length = (int) $data->character_maximum_length;
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
    }

    /**
     * Disconnect.
     * @return void
     */
    final public function disconnect(): void
    {
        $this->resource && $this->resource->close();
    }

    /**
     * Check connection.
     * @return bool
     */
    final public function isConnected(): bool
    {
        return ($this->resource && pg_connection_status($this->resource->getObject()) === PGSQL_CONNECTION_OK);
    }

    /**
     * Yes, "Query" of the S(Q)L...
     * @param  string    $query     Raw SQL query.
     * @param  array     $params    Prepare params.
     * @param  int|array $limit     Generally used in internal methods.
     * @param  int       $fetchType By-pass Result::fetchType.
     * @return Oppa\Query\Result\ResultInterface
     * @throws Oppa\Exception\{InvalidQueryException, InvalidResourceException, QueryException}
     */
    final public function query(string $query, array $params = null, $limit = null,
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

        // used for getting extra error details
        pg_set_error_verbosity($resource, PGSQL_ERRORS_VERBOSE);

        // query & query profile
        $this->profiler && $this->profiler->start(Profiler::QUERY);
        $result =@ pg_query($resource, $query);
        $this->profiler && $this->profiler->stop(Profiler::QUERY);

        if (!$result) {
            $error = $this->parseQueryError();
            try {
                throw new QueryException($error['message'], $error['code'], $error['sql_state']);
            } catch(QueryException $e) {
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

        $result = new Resource($result);

        return $this->result->process($result, $limit, $fetchType, $query);
    }

    /**
     * Count.
     * @param  ?string $table
     * @param  string  $query
     * @param  array   $params
     * @return ?int
     */
    final public function count(?string $table, string $query = null, array $params = null): ?int
    {
        if ($table) {
            $result = $this->get("SELECT reltuples::bigint AS count FROM pg_class WHERE oid = '{$table}'::regclass");
        } else {
            if (!empty($params)) {
                $query = $this->prepare($query, $params);
            }
            $result = $this->get("SELECT count(*) AS count FROM ({$query}) AS tmp");
        }

        return isset($result->count) ? intval($result->count) : null;
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
            if ($type == '%s') {
                return $this->escapeString((string) $input);
            } elseif ($type == '%b') {
                return $this->escapeBytea((string) $input);
            } else {
                return sprintf($type, $input);
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
                return $input ? 'TRUE' : 'FALSE';
            case 'double':
                return sprintf('%F', $input); // %F = non-locale aware
            case 'array':
                return join(', ', array_map([$this, 'escape'], $input)); // in/not in statements
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
    final public function escapeString(string $input, bool $quote = true): string
    {
        $input = pg_escape_string($this->resource->getObject(), $input);
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

        return pg_escape_identifier($this->resource->getObject(), trim($input, '"'));
    }

    /**
     * Escape bytea.
     * @param  string $input
     * @return string
     */
    final public function escapeBytea(string $input): string
    {
        return pg_escape_bytea($this->resource->getObject(), $input);
    }

    /**
     * Unescape bytea.
     * @param  string $input
     * @return string
     */
    final public function unescapeBytea(string $input): string
    {
        return pg_unescape_bytea($input);
    }

    /**
     * Parse connection error.
     * @return array
     */
    final private function parseConnectionError(): array
    {
        $return = ['message' => 'Unknown error.', 'code' => null, 'sql_state' => null];
        if ($error = error_get_last()) {
            $errorMessage = strstr($error['message'], "\n", true);
            if ($errorMessage === false) {
                $errorMessage = $error['message'];
            }
            $errorMessage = preg_replace('~(pg_connect\(\)|fatal):\s+~i', '', $errorMessage);
            $errorMessage = explode(':', $errorMessage);
            if (count($errorMessage) > 2) {
                $errorMessage = array_slice($errorMessage, 0, 2);
            }
            $errorMessage = implode(',', $errorMessage);

            $return['message'] = $errorMessage .'.';
            $return['sql_state'] = SqlState::OPPA_CONNECTION_ERROR;

            if (strpos($errorMessage, 'to address')) {
                $return['message'] = sprintf('Unable to connect to PostgreSQL server at "%s", '.
                    'could not translate host name "%s" to address.', $this->config['host'], $this->config['host']);
                $return['sql_state'] = SqlState::OPPA_HOST_ERROR;
            } elseif (strpos($errorMessage, 'not exist')) {
                $return['message'] = sprintf('Unable to connect to PostgreSQL server at "%s", '.
                    'database "%s" does not exist.', $this->config['host'], $this->config['name']);
                $return['sql_state'] = SqlState::OPPA_DATABASE_ERROR;
            } elseif (strpos($errorMessage, 'password authentication')) {
                $return['message'] = sprintf('Unable to connect to PostgreSQL server at "%s", '.
                    'password authentication failed for user "%s".', $this->config['host'], $this->config['username']);
                $return['sql_state'] = SqlState::OPPA_AUTHENTICATION_ERROR;
            } elseif (strpos($errorMessage, 'client_encoding')) {
                $return['message'] = sprintf('Unable to connect to PostgreSQL server at "%s", '.
                    'invalid or not-supported character set "%s" given.', $this->config['host'], $this->config['charset']);
                $return['sql_state'] = SqlState::OPPA_CHARSET_ERROR;
            } elseif (strpos($errorMessage, 'TimeZone')) {
                $return['message'] = sprintf('Unable to connect to PostgreSQL server at "%s", '.
                    'invalid or not-supported timezone "%s" given.', $this->config['host'], $this->config['timezone']);
                $return['sql_state'] = SqlState::OPPA_TIMEZONE_ERROR;
            }
        }

        return $return;
    }

    /**
     * Parse query error.
     * @return array
     */
    final private function parseQueryError(): array
    {
        $return = ['message' => 'Unknown error.', 'code' => null, 'sql_state' => null];
        if ($error = error_get_last()) {
            $error = explode("\n", $error['message']);
            // search for sql state
            preg_match('~ERROR:\s+(?<sql_state>[0-9A-Z]+?):\s+(?<message>.+)~', $error[0], $match);
            if (isset($match['sql_state'], $match['message'])) {
                // line & query details etc.
                if (isset($error[2])) {
                    preg_match('~(LINE\s+(?<line>\d+):\s+).+~', $error[1], $match2);
                    if (isset($match2['line'])) {
                        $queryCut = abs(strlen($error[1]) - strlen($error[2]));
                        $queryStr = trim(substr($error[1], -($queryCut + 2)));
                        $errorMessage = sprintf('%s, line %d. Query: "... %s"',
                            ucfirst($match['message']), $match2['line'], $queryStr);
                    }
                } else {
                    $errorMessage = $match['message'];
                }
                $return['message'] = $errorMessage .'.';
                $return['sql_state'] = $match['sql_state'];
            }
        }

        return $return;
    }
}
