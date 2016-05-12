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

namespace Oppa\Link;

use Oppa\Util;
use Oppa\Config;

/**
 * @package    Oppa
 * @subpackage Oppa\Link
 * @object     Oppa\Link\Connector
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Connector
{
    /**
     * Config.
     * @var Oppa\Config
     */
    protected $config;

    /**
     * Stack.
     * @var array
     */
    protected $connections = [];

    /**
     * Constructor.
     * @note  For all methods in this object, "$host" parameter is important, cos
     * it is used as a key to prevent to create new connections in excessive way.
     * Thus, host will be always set, even user does not pass/provide it.
     * @param Oppa\Config $config
     */
    final public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Connect.
     * @param  string|null $host
     * @return self
     * @throws \Exception
     */
    final public function connect(string $host = null): self
    {
        // connection is already active?
        if (isset($this->connections[$host])) {
            return $this;
        }

        // set type as single as default
        $type = Connection::TYPE_SINGLE;

        // get config as array
        $config = $this->config->toArray();

        // get database directives from given config
        $database = ($config['database'] ?? []);

        // is master/slave active?
        if ($this->config->get('sharding') === true) {
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
        unset($config['database'], $database['master'], $database['slaves']);

        // merge configs
        $config = $config + (array) $database;
        if (!isset($config['host'], $config['name'], $config['username'], $config['password'])) {
            throw new \Exception(
                'Please specify all needed credentials (host'.
                ', name, username, password) for connection!'
            );
        }

        // use host as a key for connection stack
        $host = $config['host'];

        // create a new connection if not exists
        if (!isset($this->connections[$host])) {
            $connection = new Connection($type, $host, new Config($config));
            $connection->open();
            $this->setConnection($host, $connection);
        }

        return $this;
    }

    /**
     * Disconnect.
     * @param  string|null $host
     * @return void
     */
    final public function disconnect(string $host = null)
    {
        // connection exists?
        if (isset($this->connections[$host])) {
            $this->connections[$host]->close();
            unset($this->connections[$host]);
        } else {
            // check by host
            switch (trim((string) $host)) {
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
            }
        }
    }

    /**
     * Check connection.
     * @param  string|null $host
     * @return bool
     */
    final public function isConnected(string $host = null): bool
    {
        // connection exists?
        // e.g: isConnected('localhost')
        if (isset($this->connections[$host])) {
            return ($this->connections[$host]->status() === Connection::STATUS_CONNECTED);
        }

        // without master/slave directives
        // e.g: isConnected()
        if (true !== $this->config->get('sharding')) {
            foreach ($this->connections as $connection) {
                return ($connection->status() === Connection::STATUS_CONNECTED);
            }
        }

        // with master/slave directives, check by host
        switch (trim((string) $host)) {
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
        }

        return false;
    }

    /**
     * Set connection.
     * @param  string                           $host
     * @param  Oppa\Link\Connection $connection
     * @return void
     */
    final public function setConnection(string $host, Connection $connection)
    {
        $this->connections[$host] = $connection;
    }

    /**
     * Get connection.
     * @param  string|null $host
     * @return Oppa\Link\Connection|null
     */
    final public function getConnection(string $host = null)
    {
        // connection exists?
        // e.g: getConnection('localhost')
        if (isset($this->connections[$host])) {
            return $this->connections[$host];
        }

        $host = trim((string) $host);
        // with master/slave directives
        if ($this->config->get('sharding') === true) {
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
    }

    /**
     * Get config.
     * @return Oppa\Config
     */
    final public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Get connections.
     * @return array
     */
    final public function getConnections(): array
    {
        return $this->connections;
    }
}
