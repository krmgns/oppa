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

namespace Oppa\Database\Connector;

use \Oppa\Helper;
use \Oppa\Configuration;
use \Oppa\Exception\Database as Exception;

/**
 * @package    Oppa
 * @subpackage Oppa\Database\Connector
 * @object     Oppa\Database\Connector\Connector
 * @uses       Oppa\Helper, Oppa\Configuration, Oppa\Exception\Database
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
final class Connector
    extends \Oppa\Shablon\Database\Connector\Connector
{
    /**
     * Create a fresh Connector object by given configuration.
     *
     * Notice: For all methods in this object, "$host" parameter is important, cos
     * it is used as a key to prevent to create new connections in excessive way.
     * Thus, host will be always set, even user does not pass/provide it.
     *
     * @param Oppa\Configuration $configuration
     */
    final public function __construct(Configuration $configuration) {
        $this->configuration = $configuration;
    }

    /**
     * Do a connection.
     *
     * @param  string $host
     * @throws Oppa\Exception\Database\ArgumentException
     * @return self
     */
    final public function connect($host = null) {
        // connection is already active?
        if (isset($this->connections[$host])) {
            return $this;
        }

        $host = trim($host);
        // set type as single as default
        $type = Connection::TYPE_SINGLE;

        // get configuration as array
        $configuration = $this->configuration->toArray();

        // get database directives from given configuration
        $database = Helper::getArrayValue('database', $configuration);

        // is master/slave active?
        if ($this->configuration->get('sharding') === true) {
            $master = Helper::getArrayValue('master', $database);
            $slaves = Helper::getArrayValue('slaves', $database);
            switch ($host) {
                // act: master as default
                case '':
                case Connection::TYPE_MASTER:
                    $type = Connection::TYPE_MASTER;
                    $database = $database + $master;
                    break;
                //  act: slave
                case Connection::TYPE_SLAVE:
                    $type = Connection::TYPE_SLAVE;
                    if (!empty($slaves)) {
                        $slave = Helper::getArrayValueRandom($slaves);
                        $database = $database + $slave;
                    }
                    break;
                default:
                    // given host is master's host?
                    if ($host == Helper::getArrayValue('host', $master)) {
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
        )) { throw new Exception\ArgumentException(
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
     * Undo a connection.
     *
     * @param  string $host
     * @throws Oppa\Exception\Database\ErrorException
     * @return void
     */
    final public function disconnect($host = null) {
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
                throw new Exception\ErrorException(
                    empty($host)
                        ? "Could not find any connection to disconnect."
                        : "Could not find any connection to disconnect with given `{$host}` host."
                );
        }
    }

    /**
     * Check a connection.
     *
     * @param  string  $host
     * @throws Oppa\Exception\Database\ErrorException
     * @return boolean
     */
    final public function isConnected($host = null) {
        // connection exists?
        // e.g: isConnected('localhost')
        if (isset($this->connections[$host])) {
            return $this->connections[$host]->status() === Connection::STATUS_CONNECTED;
        }

        // without master/slave directives
        // e.g: isConnected()
        if ($this->configuration->get('sharding') !== true) {
            foreach ($this->connections as $connection) {
                return $connection->status() === Connection::STATUS_CONNECTED;
            }
        }

        // with master/slave directives, check by host
        switch (trim($host)) {
            // e.g: isConnected(), isConnected('master')
            case '':
            case Connection::TYPE_MASTER:
                foreach ($this->connections as $connection) {
                    if ($connection->getType() == Connection::TYPE_MASTER) {
                        return $connection->status() === Connection::STATUS_CONNECTED;
                    }
                }
            // e.g: isConnected('slave1.mysql.local'), isConnected('slave')
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

    /**
     * Put a connection in connection stack using a specific host as a key.
     *
     * @param  string $host
     * @param  Oppa\Database\Connector\Connection $connection
     * @return void
     */
    final public function setConnection($host, Connection $connection) {
        $this->connections[$host] = $connection;
    }

    /**
     * Get a connection.
     *
     * @param  string $host
     * @throws Oppa\Exception\Database\ErrorException
     * @return Oppa\Database\Connector\Connection
     */
    final public function getConnection($host = null) {
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
                $connection = Helper::getArrayValueRandom(
                    array_filter($this->connections, function($connection) {
                        return $connection->getType() == Connection::TYPE_MASTER;
                }));

                if (!empty($connection)) return $connection;
            }
            // e.g: getConnection(), getConnection('slave'), getConnection('slave1.mysql.local')
            elseif ($host == Connection::TYPE_SLAVE) {
                $connection = Helper::getArrayValueRandom(
                    array_filter($this->connections, function($connection) {
                        return $connection->getType() == Connection::TYPE_SLAVE;
                }));

                if (!empty($connection)) return $connection;
            }
        } else {
            // e.g: getConnection()
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
