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

        @ $this->resource = pg_connect($connectionString);
        if (pg_connection_status($this->resource) === PGSQL_CONNECTION_BAD) {
            @ $this->resource = pg_pconnect($connectionString, PGSQL_CONNECT_FORCE_NEW);
        }

        if (!$this->resource) {
            throw new ConnectionException(error_get_last()['message'], null, SqlState::CONNECTION_FAILURE);
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
        return is_resource($this->resource) && pg_connection_status($this->resource) === PGSQL_CONNECTION_OK;
    }

    final public function query(string $query, array $params = null): Result\ResultInterface
    {}

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
