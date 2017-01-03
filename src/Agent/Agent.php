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
use Oppa\Exception\{Error, InvalidKeyException};

/**
 * @package    Oppa
 * @subpackage Oppa\Agent
 * @object     Oppa\Agent\Agent
 * @author     Kerem Güneş <k-gun@mail.com>
 */
abstract class Agent implements AgentInterface
{
    /**
     * Resource.
     * @var object|resource
     */
    protected $resource;

    /**
     * Batch.
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
     * Destructor.
     */
    final public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Isset (needed to prevent exception thrown).
     * @param  string $name
     * @return bool
     */
    final public function __isset($name): bool
    {
        return isset($this->{$name});
    }

    /**
     * Get resource.
     * @return object|resource
     */
    final public function getResource()
    {
        return $this->resource;
    }

    /**
     * Get batch.
     * @return Oppa\Batch\BatchInterface
     */
    final public function getBatch(): BatchInterface
    {
        return $this->batch;
    }

    /**
     * Get result.
     * @return Oppa\Query\Result\ResultInterface
     */
    final public function getResult(): ResultInterface
    {
        return $this->result;
    }

    /**
     * Get logger.
     * @return Oppa\Logger
     * @throws Oppa\Error
     */
    final public function getLogger()
    {
        if (!$this->logger) {
            throw new Error("Logger is not found, did you set 'query_log' option as 'true'?");
        }

        return $this->logger;
    }

    /**
     * Get mapper.
     * @return Oppa\Mapper
     * @throws Oppa\Error
     */
    final public function getMapper()
    {
        if (!$this->mapper) {
            throw new Error("Mapper is not found, did you set 'map_result' option as 'true'?");
        }

        return $this->mapper;
    }

    /**
     * Get profiler.
     * @return Oppa\Profiler
     * @throws Oppa\Error
     */
    final public function getProfiler()
    {
        if (!$this->profiler) {
            throw new Error("Profiler is not found, did you set 'profile' option as 'true'?");
        }

        return $this->profiler;
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
     * Get name.
     * @return string
     */
    final public function getName(): string
    {
        $className = get_called_class();

        return strtolower(substr($className, strrpos($className, '\\') + 1));
    }

    /**
     * Id.
     * @return ?int
     */
    final public function id(): ?int
    {
        return $this->result->getId();
    }

    /**
     * Ids.
     * @return array
     */
    final public function ids(): array
    {
        return $this->result->getIds();
    }

    /**
     * Rows count.
     * @return int
     */
    final public function rowsCount(): int
    {
        return $this->result->getRowsCount();
    }

    /**
     * Rows affected.
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
     * Prepare.
     * @param  string $input  Raw SQL complete/not complete.
     * @param  array  $params Binding params.
     * @return string
     * @throws Oppa\InvalidKeyException
     */
    final public function prepare(string $input, array $params = null): string
    {
        // any params provided?
        if (!empty($params)) {
            // available named word limits: :foo, :foo123, :foo_bar
            preg_match_all('~:([a-zA-Z0-9_]+)~', $input, $match);
            if (isset($match[1]) && !empty($match[1])) {
                $keys = $values = [];
                foreach ($match[1] as $key) {
                    if (!isset($params[$key])) {
                        throw new InvalidKeyException("Replacement named '{$key}' key not found in params!");
                    }

                    $keys[] = sprintf('~:%s~', $key);
                    $values[] = $this->escape($params[$key]);

                    // remove used params
                    unset($params[$key]);
                }
                $input = preg_replace($keys, $values, $input, 1);
            }

            // available indicator: "?"
            // available operators with type definition: "%s, %d, %f, %F"
            preg_match_all('~\?|%[sdfF]~', $input, $match);
            if (isset($match[0]) && !empty($match[0])) {
                foreach ($params as $i => $param) {
                    if (!isset($match[0][$i])) {
                        throw new InvalidKeyException("Replacement index '{$i}' key not found in input!");
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

    /**
     * Prepare "WHERE" statement.
     * @param  string $where
     * @param  array  $params
     * @return ?string
     */
    final public function where(string $where = null, array $params = null): ?string
    {
        if (!empty($params)) {
            $where = 'WHERE '. $this->prepare($where, $params);
        }

        return $where;
    }

    /**
     * Prepare "LIMIT" statement.
     * @param  int|array $limit
     * @return ?string
     */
    final public function limit($limit): ?string
    {
        if (is_array($limit)) {
            return isset($limit[0], $limit[1])
                ? sprintf('LIMIT %d OFFSET %d', $limit[0], $limit[1])
                : sprintf('LIMIT %d', $limit[0]);
        }

        return ($limit || $limit === 0 || $limit === '0') ? 'LIMIT '. intval($limit) : null;
    }

    /**
     * Get.
     * @param  string $query
     * @param  array  $params
     * @return object|array|null
     */
    final public function get(string $query, array $params = null)
    {
        return $this->query($query, $params, 1)->item(0);
    }

    /**
     * Get array.
     * @param  string $query
     * @param  array  $params
     * @return ?array
     */
    final public function getArray(string $query, array $params = null): ?array
    {
        return $this->query($query, $params, 1)->toArray()[0] ?? null;
    }

    /**
     * Get object.
     * @param  string $query
     * @param  array  $params
     * @return ?\stdClass
     */
    final public function getObject(string $query, array $params = null): ?\stdClass
    {
        return $this->query($query, $params, 1)->toObject()[0] ?? null;
    }

    /**
     * Get class.
     * @param  string $query
     * @param  array  $params
     * @return object
     */
    final public function getClass(string $query, array $params = null, string $class)
    {
        return $this->query($query, $params, 1)->toClass($class)[0] ?? null;
    }

    /**
     * Get all.
     * @param  string $query
     * @param  array  $params
     * @return array
     */
    final public function getAll(string $query, array $params = null): array
    {
        return $this->query($query, $params)->getData();
    }

    /**
     * Get all array.
     * @param  string $query
     * @param  array  $params
     * @return ?array
     */
    final public function getAllArray(string $query, array $params = null): ?array
    {
        return $this->query($query, $params)->toArray();
    }

    /**
     * Get all object.
     * @param  string $query
     * @param  array  $params
     * @return ?array
     */
    final public function getAllObject(string $query, array $params = null): ?array
    {
        return $this->query($query, $params)->toObject();
    }

    /**
     * Get all array.
     * @param  string $query
     * @param  array  $params
     * @param  string $class
     * @return ?array
     */
    final public function getAllClass(string $query, array $params = null, string $class): ?array
    {
        return $this->query($query, $params)->toClass($class);
    }
}
