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

namespace Oppa\Shablon\Database\Connector\Agent;

use \Oppa\Exception\Database as Exception;

/**
 * @package    Oppa
 * @subpackage Oppa\Shablon\Database\Connector\Agent
 * @object     Oppa\Shablon\Database\Connector\Agent\Agent
 * @uses       Oppa\Exception\Database
 * @implements Oppa\Shablon\Database\Connector\Agent\ConnectionInterface,
 *             Oppa\Shablon\Database\Connector\Agent\StreamFilterInterface,
 *             Oppa\Shablon\Database\Connector\Agent\StreamWrapperInterface
 * @version    v1.3
 * @author     Kerem Gunes <qeremy@gmail>
 */
abstract class Agent
    implements ConnectionInterface, StreamFilterInterface, StreamWrapperInterface
{
    /**
     * Connection resource, e.g \mysqli.
     * @var object|resource
     */
    protected $link;

    /**
     * Transaction object.
     * @var Oppa\Database\Batch\?
     */
    protected $batch;

    /**
     * Result object.
     * @var Oppa\Database\Query\Result\?
     */
    protected $result;

    /**
     * Logger object.
     * @var Oppa\Logger
     */
    protected $logger;

    /**
     * Mapper object.
     * @var Oppa\Mapper
     */
    protected $mapper;

    /**
     * Profiler object.
     * @var Oppa\Database\Profiler
     */
    protected $profiler;

    /**
     * Configuration.
     * @var array
     */
    protected $configuration;

    /**
     * Needed to prevent exception thrown.
     * @param  string $name
     * @return bool
     */
    public function __isset($name) {
        return isset($this->$name);
    }

    /**
     * Get link.
     *
     * @return object|resource
     */
    public function getLink() {
        return $this->link;
    }

    /**
     * Get Transaction object.
     *
     * @return Oppa\Database\Batch\?
     */
    public function getBatch() {
        return $this->batch;
    }

    /**
     * Get result object.
     *
     * @return Oppa\Database\Query\Result\?
     */
    public function getResult() {
        return $this->result;
    }

    /**
     * Get logger object.
     *
     * @throws Oppa\Exception\Database\ErrorException
     * @return Oppa\Logger
     */
    public function getLogger() {
        if (!$this->logger) {
            throw new Exception\ErrorException(
                'Profiler is not found, did you set `query_log` option as true?');
        }

        return $this->logger;
    }

    /**
     * Get mapper object.
     *
     * @throws Oppa\Exception\Database\ErrorException
     * @return Oppa\Mapper
     */
    public function getMapper() {
        if (!$this->mapper) {
            throw new Exception\ErrorException(
                'Mapper is not found, did you set `map_result` option as true?');
        }

        return $this->mapper;
    }

    /**
     * Profiler object.
     *
     * @throws Oppa\Exception\Database\ErrorException
     * @return Oppa\Database\Profiler
     */
    public function getProfiler() {
        if (!$this->profiler) {
            throw new Exception\ErrorException(
                'Profiler is not found, did you set `profiling` option as true?');
        }

        return $this->profiler;
    }

    /**
     * Get configuration.
     *
     * @return array
     */
    public function getConfiguration() {
        return $this->configuration;
    }

    /**
     * Auto detect agent class name.
     *
     * @return string
     */
    final public function getName() {
        $className = get_called_class();
        return strtolower(substr($className, strrpos($className, '\\') + 1));
    }

    /**
     * Get last insert id.
     *
     * @param  boolean $all For bulk insert actions.
     * @return mixed        If all returns array, if not integer or null
     */
    final public function id($all = false) {
        return $this->result->getId($all);
    }

    /**
     * Get row count for select actions.
     *
     * @return integer
     */
    final public function rowsCount() {
        return $this->result->getRowsCount();
    }

    /**
     * Get row count for update/delete also select actions.
     *
     * @return integer
     */
    final public function rowsAffected() {
        return $this->result->getRowsAffected();
    }

    /**
     * Why not using prepared statements?
     *
     * Yeah! This is matter...
     *
     * Fuck! Cos i cannot do this, with ie. mysqli preparing;
     * - mysqli_prepare('id = ?')
     * Need to completely query provided.
     * - mysqli_prepare('select * from users where id = ?')
     * Also, hated this;
     * - $stmt->prapere()    then
     * - $stmt->bindparam()  then
     * - $stmt->execute()    then
     * - $stmt->bindresult() then
     * - $stmt->fetch()      then
     * - $stmt->close()
     * Then what the fuck?!
     *
     * I just wanna make a query in a safe way, and do it easily, like;
     * - $users = $agent->query('select * from users where id = ?', [1])
     * That's it!..
     *
     * @param  string     $input  Raw SQL complete/not complete
     * @param  array|null $params Binding params
     * @throws Oppa\Exception\Database\ArgumentException
     * @return string
     */
    final public function prepare($input, array $params = null) {
        // any params provided?
        if (!empty($params)) {
            // available named word limits: :foo, :foo123, :foo_bar
            preg_match_all('~:([a-zA-Z0-9_]+)~', $input, $match);
            if (isset($match[1]) && !empty($match[1])) {
                $keys = $vals = $keysUsed = [];
                foreach ($match[1] as $key) {
                    if (!isset($params[$key])) {
                        throw new Exception\ArgumentException('Replacement key not found in params!');
                    }

                    $keys[] = sprintf('~:%s~', $key);
                    $vals[] = $this->escape($params[$key]);
                    $keysUsed[] = $key;
                }
                $input = preg_replace($keys, $vals, $input, 1);

                // remove used params
                foreach ($keysUsed as $key) unset($params[$key]);
            }

            // available indicator: ?
            // available operators with type definition: %s, %d, %f, %F
            preg_match_all('~\?|%[sdfF]~', $input, $match);
            if (isset($match[0]) && !empty($match[0])) {
                foreach ($params as $i => $param) {
                    if (!isset($match[0][$i])) {
                        throw new Exception\ArgumentException('Replacement key not found in input!');
                    }

                    $key = $match[0][$i];
                    $val = $this->escape($param, $key);
                    if (($pos = strpos($input, $key)) !== false) {
                        $input = substr_replace($input, $val, $pos, strlen($key));
                    }
                }
            }
        }

        return $input;
    }

    /**
     * Action pattern.
     *
     * @param  string     $where
     * @param  array|null $params
     */
    abstract public function where($where, array $params = null);

    /**
     * Action pattern.
     *
     * @param integer $limit
     */
    abstract public function limit($limit);
}
