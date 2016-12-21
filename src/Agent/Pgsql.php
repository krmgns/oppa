<?php
declare(strict_types=1);

namespace Oppa\Agent;

use Oppa\Query\{Sql, Result};
use Oppa\{Util, Config, Logger, Mapper, Profiler, Batch, SqlState\Pgsql as SqlState};
use Oppa\Exception\{Error, QueryException, ConnectionException, InvalidValueException, InvalidConfigException};

final class Pgsql //extends Agent
{ private $resource,$config;
    final public function __construct(Config $config)
    {
        // we need it like a crazy..
        if (!extension_loaded('pgsql')) {
            throw new \RuntimeException('pgsql extension is not loaded.');
        }

        $this->config = $config;
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

        $connectionType = 0;
        if (isset($options['connect_type'])) {
            foreach ($options['connect_type'] as $opt) {
                $connectionType |= $opt;
            }
        }

        if ($connectionType == 0) {
            $this->resource =@ pg_connect($connectionString);
        } else {
            $this->resource =@ pg_connect($connectionString, $connectionType);
        }

        if (!$this->resource) {
            throw new ConnectionException(error_get_last()['message'], null, SqlState::CONNECTION_FAILURE);
        }

        return $this->resource;
    }

    final public function disconnect(): void
    {
        if ($this->resource != null) {
            pg_close($this->resource);
            $this->resource = null;
        }
    }

    final public function isConnected(): bool
    {
        return ($this->resource != null && pg_connection_status($this->resource) === PGSQL_CONNECTION_OK);
    }

    final public function query(string $query, array $params = null): Result\ResultInterface
    {}
}
