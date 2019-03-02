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

namespace Oppa\Agent;

use Oppa\SqlState\SqlState;
use Oppa\Query\{Sql, Result};
use Oppa\{Util, Config, Logger, Mapper, Profiler, Batch, Resource, Cache};
use Oppa\Exception\{QueryException, ConnectionException,
    InvalidValueException, InvalidConfigException, InvalidQueryException, InvalidResourceException};

/**
 * @package Oppa
 * @object  Oppa\Agent\Pgsql
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Pgsql extends Agent
{
    /**
     * @inheritDoc Oppa\Agent\AgentInterface
     */
    public function init(Config $config): void
    {
        // we need it like a crazy..
        if (!extension_loaded('pgsql')) {
            throw new AgentException('PgSQL extension is not loaded!');
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

        if (!$resource || $resourceStatus !== PGSQL_CONNECTION_OK) {
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
            $map = $cache = null;
            if ($this->config['map_result_cache']) {
                $cache = new Cache();
                $cache->read('mapper.map', $map, true, $this->config['map_result_cache_ttl']);
            }

            if ($map == null) {
                try {
                    $result = $this->query("SELECT table_name, column_name, data_type, is_nullable, character_maximum_length FROM information_schema.columns WHERE table_schema = 'public'", null, -1, 1);
                    if ($result->count()) {
                        $map = [];
                        foreach ($result->items() as $item) {
                            $length = null;
                            if ($item->data_type == 'bit') { // for only bools
                                $length = (int) $item->character_maximum_length;
                            } elseif (substr($item->data_type, 0, 4) == 'char') { // for extra (char) crop
                                $length = (int) $item->character_maximum_length;
                            }

                            $map[$item->table_name][$item->column_name] = [
                                /* type */ Mapper::normalizeType($item->data_type),
                                /* length */ $length,
                                /* nullable */ ($item->is_nullable == 'YES')
                            ];
                        }

                        $cache && $cache->write('mapper.map', $map);
                    }
                    $result->reset();
                } catch (QueryException $e) {
                    throw new ConnectionException('Could not retrieve schema info for mapper!', null,
                        SqlState::OPPA_CONNECTION_ERROR, $e);
                }
            }

            // set map
            $this->mapper->setMap($map);
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
        return ($this->resource && pg_connection_status($this->resource->getObject()) === PGSQL_CONNECTION_OK);
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
        if ($resource == null) {
            throw new InvalidResourceException('No valid connection resource to make a query!');
        }

        if ($queryParams != null) {
            $query = $this->prepare($query, $queryParams);
        }

        // log query with info level
        $this->logger && $this->logger->log(Logger::INFO, sprintf('New query [%s] via %s addr.',
            $query, Util::getIp()));

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

        return $this->result->process(new Resource($result), $limit, $fetchType, $query);
    }

    /**
     * Count.
     * @param  string       $table
     * @param  string|array $where
     * @param  array        $whereParams
     * @param  string|null  $op
     * @return ?int
     */
    public function count(string $table, $where = null, array $whereParams = null, string $op = null): ?int
    {
        if ($where != null) {
            $query = $this->prepare('SELECT count(*) AS count FROM %n %v', [$table,
                $this->where($where, $whereParams)]);
        } else {
            $query = $this->prepare('SELECT reltuples::bigint AS count FROM pg_class WHERE '.
                'oid = \'%n\'::regclass', [$table]);
        }

        $result = (object) $this->get($query);

        return isset($result->count) ? intval($result->count) : null;
    }

    /**
     * Parse connection error.
     * @return array
     */
    private function parseConnectionError(): array
    {
        $return = ['sql_state' => null, 'code' => null, 'message' => 'Unknown error.'];
        if ($error = error_get_last()) {
            $return['sql_state'] = SqlState::OPPA_CONNECTION_ERROR;

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
     * @param  string $query
     * @return array
     */
    private function parseQueryError(string $query): array
    {
        $return = ['sql_state' => null, 'code' => null, 'message' => 'Unknown error.'];
        if ($error = error_get_last()) {
            $error = explode("\n", $error['message']);
            // search for sql state
            preg_match('~ERROR:\s+(?<sql_state>[0-9A-Z]+?):\s+(?<message>.+)~', $error[0], $match);
            if (isset($match['sql_state'], $match['message'])) {
                $return['sql_state'] = $match['sql_state'];
                // line & query details etc.
                if (count($error) == 3 && preg_match('~(LINE\s+(?<line>\d+):\s+).+~', $error[1], $match2)) {
                    $return['message'] = sprintf('%s, line %d. Query: "%s".', ucfirst($match['message']),
                        $match2['line'], $query);
                } else {
                    $return['message'] = $match['message'] .'.';
                }
            }
        }

        return $return;
    }
}
