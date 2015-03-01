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

namespace Oppa\Shablon\Database\Connector;

use \Oppa\Database\Connector\Connection;

/**
 * @package    Oppa
 * @subpackage Oppa\Shablon\Database\Connector
 * @object     Oppa\Shablon\Database\Connector\Connector
 * @uses       Oppa\Database\Connector\Connection
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
abstract class Connector
{
    /**
     * Connection configuration.
     * @var Oppa\Configuration
     */
    protected $configuration;

    /**
     * Connections stack.
     * @var array
     */
    protected $connections = [];

    /**
     * Action pattern.
     *
     * @param string $host
     */
    abstract public function connect($host = null);

    /**
     * Action pattern.
     *
     * @param string $host
     */
    abstract public function disconnect($host = null);

    /**
     * Action pattern.
     *
     * @param string $host
     */
    abstract public function isConnected($host = null);

    /**
     * Action pattern.
     *
     * @param string $host
     * @param Oppa\Database\Connector\Connection $connection
     */
    abstract public function setConnection($host, Connection $connection);

    /**
     * Action pattern.
     *
     * @param string $host
     */
    abstract public function getConnection($host = null);
}
