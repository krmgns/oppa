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

namespace Oppa\Agent;

use Oppa\Config;
use Oppa\Batch\BatchInterface;
use Oppa\Query\Result\ResultInterface;

/**
 * @package    Oppa
 * @subpackage Oppa\Agent
 * @object     Oppa\Agent\Agent
 * @author     Kerem Güneş <k-gun@mail.com>
 */
abstract class Agent implements AgentInterface
{
    /**
     * Link.
     * @var object|resource
     */
    protected $link;

    /**
     * Transaction.
     * @var Oppa\Batch\BatchInterface
     */
    protected $batch;

    /**
     * Result.
     * @var Oppa\Query\Result\ResultInterface
     */
    protected $result;

    /**
     * Logger.
     * @var Oppa\Logger
     */
    protected $logger;

    /**
     * Mapper.
     * @var Oppa\Mapper
     */
    protected $mapper;

    /**
     * Profiler.
     * @var Oppa\Profiler
     */
    protected $profiler;

    /**
     * Config.
     * @var Oppa\Config
     */
    protected $config;

    /**
     * Needed to prevent exception thrown.
     * @param  string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->{$name});
    }

    /**
     * Get link.
     *
     * @return object|resource
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Get transaction.
     * @return Oppa\Batch\BatchInterface
     */
    public function getBatch(): BatchInterface
    {
        return $this->batch;
    }

    /**
     * Get result.
     * @return Oppa\Query\Result\ResultInterface
     */
    public function getResult(): ResultInterface
    {
        return $this->result;
    }

    /**
     * Get logger.
     * @return Oppa\Logger
     * @throws \Exception
     */
    public function getLogger()
    {
        if (!$this->logger) {
            throw new \Exception('Profiler is not found, did you set `query_log` option as true?');
        }

        return $this->logger;
    }

    /**
     * Get mapper.
     * @return Oppa\Mapper
     * @throws \Exception
     */
    public function getMapper()
    {
        if (!$this->mapper) {
            throw new \Exception('Mapper is not found, did you set `map_result` option as true?');
        }

        return $this->mapper;
    }

    /**
     * Get profiler.
     * @return Oppa\Profiler
     * @throws \Exception
     */
    public function getProfiler()
    {
        if (!$this->profiler) {
            throw new \Exception('Profiler is not found, did you set `profiling` option as true?');
        }

        return $this->profiler;
    }

    /**
     * Get config.
     * @return Oppa\Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Auto detect agent class name.
     * @return string
     */
    final public function getName(): string
    {
        $className = get_called_class();

        return strtolower(substr($className, strrpos($className, '\\') + 1));
    }

    /**
     * Get last insert id.
     * @param  bool $all For bulk insert actions.
     * @return any  If all returns array, if not int or null.
     */
    final public function id($all = false)
    {
        return $this->result->getId($all);
    }

    /**
     * Get row count for select actions.
     * @return int
     */
    final public function rowsCount(): int
    {
        return $this->result->getRowsCount();
    }

    /**
     * Get row count for update/delete also select actions.
     * @return int
     */
    final public function rowsAffected(): int
    {
        return $this->result->getRowsAffected();
    }

    /**
     * Why not using prepared statements? Yeah! This is the matter...
     *
     * Fuck! Cos i cannot do this, with ie. mysqli preparing;
     * - mysqli_prepare('id = ?')
     * Need to completely query provided.
     * - mysqli_prepare('select * from users where id = ?')
     * Also, hated this;
     * - $stmt->prepare()    then
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
     * @param  string $input  Raw SQL complete/not complete.
     * @param  array  $params Binding params.
     * @return string
     * @throws \InvalidArgumentException
     */
    final public function prepare(string $input, array $params = null): string
    {
        // any params provided?
        if (!empty($params)) {
            // available named word limits: :foo, :foo123, :foo_bar
            preg_match_all('~:([a-zA-Z0-9_]+)~', $input, $match);
            if (isset($match[1]) && !empty($match[1])) {
                $keys = $values = $keysUsed = [];
                foreach ($match[1] as $key) {
                    if (!isset($params[$key])) {
                        throw new \InvalidArgumentException("Replacement '{$key}' key not found in params!");
                    }

                    $keys[] = sprintf('~:%s~', $key);
                    $values[] = $this->escape($params[$key]);
                    $keysUsed[] = $key;
                }
                $input = preg_replace($keys, $values, $input, 1);

                // remove used params
                foreach ($keysUsed as $key) unset($params[$key]);
            }

            // available indicator: "?"
            // available operators with type definition: "%s, %d, %f, %F"
            preg_match_all('~\?|%[sdfF]~', $input, $match);
            if (isset($match[0]) && !empty($match[0])) {
                foreach ($params as $i => $param) {
                    if (!isset($match[0][$i])) {
                        throw new \InvalidArgumentException("Replacement '{$i}' key not found in input!");
                    }

                    $key = $match[0][$i];
                    $value = $this->escape($param, $key);
                    if (false !== ($pos = strpos($input, $key))) {
                        $input = substr_replace($input, $value, $pos, strlen($key));
                    }
                }
            }
        }

        return $input;
    }
}
