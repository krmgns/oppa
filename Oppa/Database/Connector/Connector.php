<?php namespace Oppa\Database\Connector;

use \Oppa\Helper;
use \Oppa\Configuration;
use \Oppa\Exception\Database as Exception;

final class Connector
    extends \Oppa\Shablon\Database\Connector\Connector
{
    final public function __construct(Configuration $configuration) {
        $this->configuration = $configuration;
    }

    final public function connect($host = null) {
        if (isset($this->connections[$host])) {
            return $this;
        }

        $host = trim($host);
        $type = Connection::TYPE_SINGLE;

        $configuration = $this->configuration->toArray();
        $database      = Helper::getArrayValue('database', $configuration);

        if ($this->configuration->get('sharding') === true) {
            $master = Helper::getArrayValue('master', $database);
            $slaves = Helper::getArrayValue('slaves', $database);
            switch ($host) {
                case '':
                case Connection::TYPE_MASTER:
                    $type = Connection::TYPE_MASTER;
                    $database = $database + $master;
                    break;
                case Connection::TYPE_SLAVE:
                    $type = Connection::TYPE_SLAVE;
                    if (!empty($slaves)) {
                        $slave = Helper::getArrayValueRandom($slaves);
                        $database = $database + $slave;
                    }
                    break;
                default:
                    if ($host == Helper::getArrayValue('host', $master)) {
                        $type = Connection::TYPE_MASTER;
                        $database = $database + $master;
                    } else {
                        $type = Connection::TYPE_SLAVE;
                        foreach ($slaves as $slave) {
                            if (isset($slave['host'], $slave['name']) && $slave['host'] == $host) {
                                $database = $database + $slave;
                                break;
                            }
                        }
                    }
            }
        }

        unset($configuration['database']);
        unset($database['master'], $database['slaves']);

        $configuration = $configuration + $database;
        if (!isset(
            $configuration['host'],
            $configuration['name'],
            $configuration['username'],
            $configuration['password']
        )) { throw new Exception\ArgumentException(
                'Please specify all needed credentials (host'.
                ', name, username, password) for connection!');
        }

        $host = $configuration['host'];
        if (!isset($this->connections[$host])) {
            $connection = new Connection($type, $host, $configuration);
            $connection->open();
            $this->setConnection($host, $connection);
        }

        return $this;
    }

    final public function disconnect($host = null) {
        if (isset($this->connections[$host])) {
            $this->connections[$host]->close();
            unset($this->connections[$host]);
            return;
        }

        switch (trim($host)) {
            case '':
            case '*':
                foreach ($this->connections as $i => $connection) {
                    $connection->close();
                    unset($this->connections[$i]);
                }
                break;
            case Connection::TYPE_MASTER:
                foreach ($this->connections as $i => $connection) {
                    if ($connection->getType() == Connection::TYPE_MASTER) {
                        $connection->close();
                        unset($this->connections[$i]);
                        break;
                    }
                }
                break;
            case Connection::TYPE_SLAVE:
                foreach ($this->connections as $i => $connection) {
                    if ($connection->getType() == Connection::TYPE_SLAVE) {
                        $connection->close();
                        unset($this->connections[$i]);
                    }
                }
                break;
            default:
                throw new Exception\ErrorException(
                    empty($host)
                        ? "Could not find any connection to disconnect."
                        : "Could not find any connection to disconnect with given `{$host}` host."
                );
        }
    }

    final public function isConnected($host = null) {
        if (isset($this->connections[$host])) {
            return $this->connections[$host]->status() === Connection::STATUS_CONNECTED;
        }

        if ($this->configuration->get('sharding') !== true) {
            foreach ($this->connections as $connection) {
                return $connection->status() === Connection::STATUS_CONNECTED;
            }
        }

        switch (trim($host)) {
            case '':
            case Connection::TYPE_MASTER:
                foreach ($this->connections as $connection) {
                    if ($connection->getType() == Connection::TYPE_MASTER) {
                        return $connection->status() === Connection::STATUS_CONNECTED;
                    }
                }
            case Connection::TYPE_SLAVE:
                foreach ($this->connections as $connection) {
                    if ($connection->getType() == Connection::TYPE_SLAVE) {
                        return $connection->status() === Connection::STATUS_CONNECTED;
                    }
                }
                break;
        }

        throw new Exception\ErrorException(
            empty($host)
                ? "Could not find any connection to check."
                : "Could not find any connection to check with given `{$host}` host."
        );
    }

    final public function setConnection($host, Connection $connection) {
        $this->connections[$host] = $connection;
    }

    final public function getConnection($host = null) {
        if (isset($this->connections[$host])) {
            return $this->connections[$host];
        }

        $host = trim($host);
        if ($this->configuration->get('sharding') === true) {
            if ($host == '' || $host == Connection::TYPE_MASTER) {
                $connection = Helper::getArrayValueRandom(
                    array_filter($this->connections, function($connection) {
                        return $connection->getType() == Connection::TYPE_MASTER;
                }));
                if (!empty($connection)) return $connection;
            } elseif ($host == Connection::TYPE_SLAVE) {
                $connection = Helper::getArrayValueRandom(
                    array_filter($this->connections, function($connection) {
                        return $connection->getType() == Connection::TYPE_SLAVE;
                }));
                if (!empty($connection)) return $connection;
            }
        } else {
            if ($host == '') {
                $connection = Helper::getArrayValueRandom(
                    array_filter($this->connections, function($connection) {
                        return $connection->getType() == Connection::TYPE_SINGLE;
                }));
                if (!empty($connection)) return $connection;
            }
        }

        throw new Exception\ErrorException(
            empty($host)
                ? "Could not find any connection to return."
                : "Could not find any connection to return with given `{$host}` host."
        );
    }
}
