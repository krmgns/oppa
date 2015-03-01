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

namespace Oppa;

use \Oppa\Configuration;
use \Oppa\Database\Connector\Connector;

/**
 * @package    Oppa
 * @object     Oppa\Database
 * @uses       Oppa\Configuration, Oppa\Database\Connector\Connector
 * @implements Oppa\Shablon\Database\DatabaseInterface
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
final class Database
    implements \Oppa\Shablon\Database\DatabaseInterface
{
    /**
     * Database info.
     * @var array
     */
    private $info; // @notimplemented

    /**
     * Database connector object.
     * @var Oppa\Database\Connector\Connector
     */
    private $connector;

    /**
     * Create a fresh Database object with given configuration.
     *
     * @param Oppa\Configuration $configuration
     */
    final public function __construct(Configuration $configuration) {
        $this->connector = new Connector($configuration);
    }

    /**
     * Do a connection via connector.
     *
     * @param  string|null $host
     * @return Oppa\Database\Connector\Connector
     */
    final public function connect($host = null) {
        return $this->connector->connect($host);
    }

    /**
     * Undo a connection via connector.
     *
     * @param  string|null $host
     * @return void
     */
    final public function disconnect($host = null) {
        return $this->connector->disconnect($host);
    }

    /**
     * Check a connection via connector.
     *
     * @param  string|null  $host
     * @return boolean
     */
    final public function isConnected($host = null) {
        return $this->connector->isConnected($host);
    }

    /**
     * Get a connection via connector.
     *
     * @param  string|null $host
     * @return Oppa\Database\Connector\Connection
     */
    final public function getConnection($host = null) {
        return $this->connector->getConnection($host);
    }

    // @notimplemented
    final public function info() {}
}
