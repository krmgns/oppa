<?php
declare(strict_types=1);

namespace Oppa\Agent;

use Oppa\Query\{Sql, Result};
use Oppa\{Util, Config, Logger, Mapper, Profiler, Batch, SqlState\Pgsql as SqlState};
use Oppa\Exception\{Error, QueryException, ConnectionException, InvalidValueException, InvalidConfigException};

final class Pgsql extends Agent
{
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

    final public function connect()
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

        // start connection profiling
        $this->profiler && $this->profiler->start(Profiler::CONNECTION);

        $this->resource = pg_connect($connectionString);
        if (pg_connection_status($this->resource) === PGSQL_CONNECTION_BAD) {
            $this->resource = pg_pconnect($connectionString, PGSQL_CONNECT_FORCE_NEW);
        }

        if (!$this->resource) {
            throw new ConnectionException(error_get_last()['message'], null, SqlState::CONNECTION_FAILURE);
        }

        // finish connection profiling
        $this->profiler && $this->profiler->stop(Profiler::CONNECTION);

        // log with info level
        $this->logger && $this->logger->log(Logger::INFO, sprintf('New connection via %s addr.', Util::getIp()));

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

        return $this->resource;
    }

    final public function disconnect(): void
    {
        if (is_resource($this->resource)) {
            pg_close($this->resource);
            $this->resource = null;
        }
    }

    final public function isConnected(): bool
    {
        return pg_connection_status($this->resource) === PGSQL_CONNECTION_OK;
    }

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

        // used for getting extra query details
        pg_set_error_verbosity($this->resource, PGSQL_ERRORS_VERBOSE);

        // start last query profiling
        $this->profiler && $this->profiler->start(Profiler::QUERY);

        $result = pg_query($this->resource, $query);

        // finish last query profiling
        $this->profiler && $this->profiler->stop(Profiler::QUERY);

        if (!$result) {
            $error = $this->parseError();
            try {
                throw new QueryException($error['error'], null, $error['sqlstate']);
            } catch(QueryException $e) {
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

        $result = $this->result->process($result, $limit, $fetchType);

        // last insert id
        if (stripos($query, 'insert') !== false) {
            $idResult = pg_query($this->resource, 'SELECT lastval() AS id');
            if ($idResult) {
                $id = (int) pg_fetch_result($idResult, 'id');
                if ($id) {
                    // multiple inserts
                    $rowsAffected = $result->getRowsAffected();
                    if ($rowsAffected > 1) {
                        $id = range($id - $rowsAffected + 1, $id);
                    }
                    $result->setIds((array) $id);
                }
                pg_free_result($idResult);
            }
        }

        return $result;
    }

    final public function select(string $table, $fields = null, string $where = null,
        array $params = null, $limit = null, int $fetchType = null) {}
    final public function selectOne(string $table, $fields = null, string $where = null,
        array $params = null, int $fetchType = null){}

    final public function insert(string $table, array $data) {}
    final public function update(string $table, array $data, string $where = null,
        array $params = null, $limit = null): int {}
    final public function delete(string $table, string $where = null,
        array $params = null, $limit = null): int {}

    final public function count(string $query): int {}

    final public function escape($input, string $type = null)
    {
        $inputType = gettype($input);

        // escape strings %s and for all formattable types like %d, %f and %F
        if ($inputType != 'array' && $type && $type[0] == '%') {
            if ($type == '%b') {
                return $this->escapeBytea((string) $input);
            } elseif ($type != '%s') {
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
                throw new InvalidValueException(sprintf("Unimplemented '{$inputType}' type encountered!"));
        }

        return $input;
    }

    final public function escapeString(string $input, bool $quote = true): string
    {
        $input = pg_escape_string($this->resource, $input);
        if ($quote) {
            $input = "'{$input}'";
        }
        return $input;
    }

    final public function escapeIdentifier($input): string
    {
        if ($input == '*') {
            return $input;
        }

        if (is_array($input)) {
            return join(', ', array_map([$this, 'escapeIdentifier'], $input));
        }

        return pg_escape_identifier($this->resource, trim($input, ' "'));
    }

    final public function escapeBytea(string $input): string
    {
        return pg_escape_bytea($this->resource, $input);
    }
    final public function unescapeBytea(string $input): string
    {
        return pg_unescape_bytea($input);
    }

    final private function parseError(): ?array
    {
        $return = null;
        if ($error = pg_last_error($this->resource)) {
            $error = explode(PHP_EOL, $error);
            preg_match('~ERROR:\s+([0-9A-Z]+?):\s+(.+)~', $error[0], $match);
            if (isset($match[1], $match[2])) {
                $return = ['sqlstate' => $match[1]];
                if (isset($error[2])) {
                    preg_match('~(LINE\s+(\d+):\s+).+~', $error[1], $match2);
                    if (isset($match2[1], $match2[2])) {
                        $nearbyCut = abs(strlen($error[1]) - strlen($error[2]));
                        $nearbyStr = trim(substr($error[1], -($nearbyCut + 1)));
                        $return['error'] = sprintf('%s, line %s, nearby "... %s"', $match[2], $match2[2], $nearbyStr);
                    }
                } else {
                    $return['error'] = $match[2];
                }
            }
        }
        return $return;
    }
}
