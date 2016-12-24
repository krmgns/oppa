<?php
declare(strict_types=1);

namespace Oppa\Agent;

use Oppa\Query\{Sql, Result};
use Oppa\{Util, Config, Logger, Mapper, Profiler, Batch, SqlState\Pgsql as SqlState};
use Oppa\Exception\{Error, QueryException, ConnectionException, InvalidValueException, InvalidConfigException};

final class Pgsql extends Agent
{   //private $resource,$config;
    final public function __construct(Config $config)
    {
        // we need it like a crazy..
        if (!extension_loaded('pgsql')) {
            throw new \RuntimeException('pgsql extension is not loaded.');
        }

        $this->config = $config;

        if ($this->config['map_result']) {
            $this->mapper = new Mapper();
            if (isset($this->config['map_result_bool'])) {
                $this->mapper->setMapOptions(['bool' => (bool) $this->config['map_result_bool']]);
            }
        }

        $this->result = new Result\Pgsql($this);
        $this->result->setFetchType($this->config['fetch_type'] ?? Result\Result::AS_OBJECT);
    }

    final public function __destruct()
    {
        $this->disconnect();
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

        @ $this->resource = pg_connect($connectionString);
        if (pg_connection_status($this->resource) === PGSQL_CONNECTION_BAD) {
            @ $this->resource = pg_pconnect($connectionString, PGSQL_CONNECT_FORCE_NEW);
        }

        if (!$this->resource) {
            throw new ConnectionException(error_get_last()['message'], null, SqlState::CONNECTION_FAILURE);
        }
        if ($this->mapper) {
            try {
                $this->query("SELECT table_name, column_name, data_type, is_nullable
                    FROM information_schema.columns WHERE table_schema = 'public'");
                if ($this->result->count()) {
                    $map = [];
                    foreach ($this->result as $result) {
                        $map[$result->table_name][$result->column_name]['type'] = $result->data_type;
                        $map[$result->table_name][$result->column_name]['length'] = null;
                        $map[$result->table_name][$result->column_name]['nullable'] = ($result->is_nullable == 'YES');
                    }
                    $this->mapper->setMap($map);
                }
                $this->result->reset();
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
        pg_set_error_verbosity($this->resource, PGSQL_ERRORS_VERBOSE);

        @ $result = pg_query($this->resource, $query);
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
        if (false !== stripos($query, 'insert')) {
            @ $idResult = pg_query($this->resource, 'SELECT lastval() AS id');
            if ($idResult) {
                $id = (int) pg_fetch_result($idResult, 'id');
                if ($id) {
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
        array $params = null, $limit = null, int $fetchType = null)
    {}
    final public function selectOne(string $table, $fields = null, string $where = null,
        array $params = null, int $fetchType = null)
    {
        return $this->select($table, $fields, $where, $params, 1, $fetchType)[0] ?? null;
    }
    final public function insert(string $table, array $data) {}
    final public function update(string $table, array $data, string $where = null,
        array $params = null, $limit = null): int {}
    final public function delete(string $table, string $where = null,
        array $params = null, $limit = null): int {}
    final public function count(string $query): int {}
    final public function escape($input, string $type = null) {}
    final public function escapeIdentifier($input): string {}
    final public function where(string $where = null, array $params = null): ?string {}
    final public function limit($limit): string {}

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
