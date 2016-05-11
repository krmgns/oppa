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

namespace Oppa\Database\Connector;

use Oppa\Util;
use Oppa\Configuration;

/**
 * @package    Oppa
 * @subpackage Oppa\Database\Connector
 * @object     Oppa\Database\Connector\Connector
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Connector extends \Oppa\Shablon\Database\Connector\Connector
{
    /**
     * Constructor.
     * @note  For all methods in this object, "$host" parameter is important, cos
     * it is used as a key to prevent to create new connections in excessive way.
     * Thus, host will be always set, even user does not pass/provide it.
     * @param Oppa\Configuration $configuration
     */
    final public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Create a connection.
     * @param  string $host
     * @return self
     * @throws \Exception
     */
    final public function connect(string $host = null)
    {
        // connection is already active?
        if (isset($this->connections[$host])) {
            return $this;
        }

        // set type as single as default
        $type = Connection::TYPE_SINGLE;

        // get configuration as array
        $configuration = $this->configuration->toArray();

        // get database directives from given configuration
        $database = ($configuration['database'] ?? []);

        // is master/slave active?
        if ($this->configuration->get('sharding') === true) {
            $master = ($database['master'] ?? []);
            $slaves = ($database['slaves'] ?? []);
            switch ($host) {
                // act: master as default
                case null:
                case Connection::TYPE_MASTER:
                    $type = Connection::TYPE_MASTER;
                    $database = $database + $master;
                    break;
                //  act: slave
                case Connection::TYPE_SLAVE:
                    $type = Connection::TYPE_SLAVE;
                    if (!empty($slaves)) {
                        $slave = Util::arrayRand($slaves);
                        $database = $database + $slave;
                    }
                    break;
                default:
                    // given host is master's host?
                    if ($host == ($master['host'] ?? '')) {
                        $type = Connection::TYPE_MASTER;
                        $database = $database + $master;
                    } else {
                        // or given host is slaves's host?
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

        // remove unused parts
        unset($configuration['database']);
        unset($database['master'], $database['slaves']);

        // merge configurations
        $configuration = $configuration + (array) $database;
        if (!isset(
            $configuration['host'], $configuration['name'],
            $configuration['username'], $configuration['password']
        )) { throw new \Exception(
                'Please specify all needed credentials (host'.
                ', name, username, password) for connection!');
        }

        // use host as a key for connection stack
        $host = $configuration['host'];

        // create a new connection if not exists
        if (!isset($this->connections[$host])) {
            $connection = new Connection($type, $host, $configuration);
            $connection->open();
            $this->setConnection($host, $connection);
        }

        return $this;
    }

    /**
     * Cancel a connection.
     * @param  string $host
     * @return void
     * @throws \Exception
     */
    final public function disconnect(string $host = null)
    {
        // connection exists?
        if (isset($this->connections[$host])) {
            $this->connections[$host]->close();
            unset($this->connections[$host]);

            return;
        }

        // check by host
        switch (trim($host)) {
            // remove all connections
            case '':
            case '*':
                foreach ($this->connections as $i => $connection) {
                    $connection->close();
                    unset($this->connections[$i]);
                }
                break;
            // remove master connection
            case Connection::TYPE_MASTER:
                foreach ($this->connections as $i => $connection) {
                    if ($connection->getType() == Connection::TYPE_MASTER) {
                        $connection->close();
                        unset($this->connections[$i]);
                        break;
                    }
                }
                break;
            // remove slave connections
            case Connection::TYPE_SLAVE:
                foreach ($this->connections as $i => $connection) {
                    if ($connection->getType() == Connection::TYPE_SLAVE) {
                        $connection->close();
                        unset($this->connections[$i]);
                    }
                }
                break;
            default:
                throw new \Exception(
                    empty($host)
                        ? "Could not find any connection to disconnect."
                        : "Could not find any connection to disconnect with given `{$host}` host."
                );
        }
    }

    /**
     * Check a connection.
     * @param  string $host
     * @return bool
     * @throws \Exception
     */
    final public function isConnected(string $host = null)
    {
        // connection exists?
        // e.g: isConnected('localhost')
        if (isset($this->connections[$host])) {
            return ($this->connections[$host]->status() === Connection::STATUS_CONNECTED);
        }

        // without master/slave directives
        // e.g: isConnected()
        if ($this->configuration->get('sharding') !== true) {
            foreach ($this->connections as $connection) {
                return ($connection->status() === Connection::STATUS_CONNECTED);
            }
        }

        // with master/slave directives, check by host
        switch (trim($host)) {
            // e.g: isConnected(), isConnected('master')
            case '':
            case Connection::TYPE_MASTER:
                foreach ($this->connections as $connection) {
                    if ($connection->getType() == Connection::TYPE_MASTER) {
                        return ($connection->status() === Connection::STATUS_CONNECTED);
                    }
                }
            // e.g: isConnected('slave1.mysql.local'), isConnected('slave')
            case Connection::TYPE_SLAVE:
                foreach ($this->connections as $connection) {
                    if ($connection->getType() == Connection::TYPE_SLAVE) {
                        return ($connection->status() === Connection::STATUS_CONNECTED);
                    }
                }
                break;
        }

        throw new \Exception(
            empty($host)
                ? "Could not find any connection to check."
                : "Could not find any connection to check with given `{$host}` host."
        );
    }

    /**
     * Put a connection in connection stack using a specific host as a key.
     * @param  string $host
     * @param  Oppa\Database\Connector\Connection $connection
     * @return void
     */
    final public function setConnection(string $host, Connection $connection)
    {
        $this->connections[$host] = $connection;
    }

    /**
     * Get a connection.
     * @param  string $host
     * @return Oppa\Database\Connector\Connection
     * @throws \Exception
     */
    final public function getConnection(string $host = null)
    {
        // connection exists?
        // e.g: getConnection('localhost')
        if (isset($this->connections[$host])) {
            return $this->connections[$host];
        }

        $host = trim($host);
        // with master/slave directives
        if ($this->configuration->get('sharding') === true) {
            // e.g: getConnection(), getConnection('master'), getConnection('master.mysql.local')
            if ($host == '' || $host == Connection::TYPE_MASTER) {
                $connection = Util::arrayRand(
                    array_filter($this->connections, function($connection) {
                        return $connection->getType() == Connection::TYPE_MASTER;
                }));

                if (!empty($connection)) return $connection;
            }
            // e.g: getConnection(), getConnection('slave'), getConnection('slave1.mysql.local')
            elseif ($host == Connection::TYPE_SLAVE) {
                $connection = Util::arrayRand(
                    array_filter($this->connections, function($connection) {
                        return $connection->getType() == Connection::TYPE_SLAVE;
                }));

                if (!empty($connection)) return $connection;
            }
        } else {
            // e.g: getConnection()
            if ($host == '') {
                $connection = Util::arrayRand(
                    array_filter($this->connections, function($connection) {
                        return $connection->getType() == Connection::TYPE_SINGLE;
                }));

                if (!empty($connection)) return $connection;
            }
        }

        throw new \Exception(
            empty($host)
                ? "Could not find any connection to return."
                : "Could not find any connection to return with given `{$host}` host."
        );
    }
}
