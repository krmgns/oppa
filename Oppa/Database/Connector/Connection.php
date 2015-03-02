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
use \Oppa\Exception\Database as Exception;

/**
 * @package    Oppa
 * @subpackage Oppa\Database\Connector
 * @object     Oppa\Database\Connector\Connection
 * @uses       Oppa\Helper, Oppa\Exception\Database
 * @extends    Oppa\Shablon\Database\Connector\Connection
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
final class Connection
    extends \Oppa\Shablon\Database\Connector\Connection
{
    /**
     * Create a fresh Connection object.
     *
     * @param string $type
     * @param string $host
     * @param array  $configuration
     */
    final public function __construct($type, $host, array $configuration) {
        $this->type = $type;
        $this->host = $host;
        $this->configuration = $configuration;
    }

    /**
     * Open a connection, attach agent if not exists.
     *
     * @return void
     */
    final public function open() {
        if (!isset($this->agent)) {
            // attach agent first
            $this->attachAgent();
            // and open connection
            $this->agent->connect();
        }
    }

    /**
     * Close a connection, detach agent.
     *
     * @return void
     */
    final public function close() {
        if (isset($this->agent)) {
            // close connection first
            $this->agent->disconnect();
            // and detach agent
            $this->detachAgent();
        }
    }

    /**
     * Check connection status.
     *
     * @return mixed
     *   - if agent is exists       @return integer
     *   - if agent does not exists @return boolean (false)
     */
    final public function status() {
        if (isset($this->agent)) {
            return $this->agent->isConnected()
                ? self::STATUS_CONNECTED : self::STATUS_DISCONNECTED;
        }

        return false;
    }

    /**
     * Attach agent to work with database.
     *
     * @throws Oppa\Exception\Database\ValueException
     * @return void
     */
    final protected function attachAgent() {
        $agentName =@ strtolower($this->configuration['agent']);
        switch ($agentName) {
            // for now, only mysqli
            // if time permits, i will extend..
            case self::AGENT_MYSQLI:
                $this->agent = new Agent\Mysqli($this->configuration);
                $this->agentName = $agentName;
                break;
            default:
                throw new Exception\ValueException(
                    "Sorry, but `{$agentName}` agent not implemented!");
        }
    }

    /**
     * Detach agent.
     *
     * @return void
     */
    final protected function detachAgent() {
        $this->agent = null;
        $this->agentName = null;
    }
}
