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

use Oppa\{Config, Resource};
use Oppa\Batch\BatchInterface;
use Oppa\Query\Result\ResultInterface;
use Oppa\Exception\{Error, InvalidKeyException};

/**
 * @package Oppa
 * @object  Oppa\Agent\Agent
 * @author  Kerem Güneş <k-gun@mail.com>
 */
abstract class Agent extends AgentCrud implements AgentInterface
{
    /**
     * Resource.
     * @var Resource
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
    public final function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Isset (needed to prevent exception thrown).
     * @param  string $name
     * @return bool
     */
    public final function __isset($name)
    {
        return isset($this->{$name});
    }

    /**
     * Get resource.
     * @return Oppa\Resource
     */
    public final function getResource(): Resource
    {
        return $this->resource;
    }

    /**
     * Get resource stats.
     * @return ?array
     */
    public final function getResourceStats(): ?array
    {
        $return = null;
        if ($this->resource->getType() == Resource::TYPE_MYSQL_LINK) {
            $result = $this->resource->getObject()->query('SHOW SESSION STATUS');
            while ($row = $result->fetch_assoc()) {
                $return[strtolower($row['Variable_name'])] = $row['Value'];
            }
            $result->free();
        } elseif ($this->resource->getType() == Resource::TYPE_PGSQL_LINK) {
            $result = pg_query($this->resource->getObject(),
                "SELECT * FROM pg_stat_activity WHERE usename = '". $this->config['username'] ."'");
            $resultArray = pg_fetch_all($result);
            if (isset($resultArray[0])) {
                $return = $resultArray[0];
            }
            pg_free_result($result);
        }

        return $return;
    }

    /**
     * Get batch.
     * @return Oppa\Batch\BatchInterface
     */
    public final function getBatch(): BatchInterface
    {
        return $this->batch;
    }

    /**
     * Get result.
     * @return Oppa\Query\Result\ResultInterface
     */
    public final function getResult(): ResultInterface
    {
        return $this->result;
    }

    /**
     * Get logger.
     * @return Oppa\Logger
     * @throws Oppa\Error
     */
    public final function getLogger()
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
    public final function getMapper()
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
    public final function getProfiler()
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
    public final function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Get name.
     * @return string
     */
    public final function getName(): string
    {
        $className = get_called_class();

        return strtolower(substr($className, strrpos($className, '\\') + 1));
    }

    /**
     * Id.
     * @return ?int
     */
    public final function id(): ?int
    {
        return $this->result->getId();
    }

    /**
     * Ids.
     * @return array
     */
    public final function ids(): array
    {
        return $this->result->getIds();
    }

    /**
     * Rows count.
     * @return int
     */
    public final function rowsCount(): int
    {
        return $this->result->getRowsCount();
    }

    /**
     * Rows affected.
     * @return int
     */
    public final function rowsAffected(): int
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
     * @param  string $input       Raw SQL complete/not complete.
     * @param  array  $inputParams Binding params.
     * @return string
     * @throws Oppa\Exception\InvalidKeyException
     */
    public final function prepare(string $input, array $inputParams = null): string
    {
        // any params provided?
        if (!empty($inputParams)) {
            // available named word limits: :foo, :foo123, :foo_bar
            preg_match_all('~(?<!:):([a-zA-Z0-9_]+)~', $input, $match);
            if (!empty($match[1])) {
                $keys = $values = [];
                $match[1] = array_unique($match[1]);
                foreach ($match[1] as $key) {
                    if (!array_key_exists($key, $inputParams)) {
                        throw new InvalidKeyException("Replacement key '{$key}' not found in params!");
                    }

                    $keys[] = sprintf('~:%s~', $key);
                    $values[] = $this->escape($inputParams[$key]);

                    // remove used params
                    unset($inputParams[$key]);
                }
                $input = preg_replace($keys, $values, $input);
            }

            // available indicator: "?"
            // available operators with type definition: "%s, %i, %f, %v, %n"
            preg_match_all('~\?|%[sifvn]~', $input, $match);
            if (!empty($match[0])) {
                foreach ($inputParams as $i => $inputParam) {
                    if (!array_key_exists($i, $match[0])) {
                        throw new InvalidKeyException("Replacement index '{$i}' key not found in input!");
                    }

                    $key = $match[0][$i];
                    $value = $inputParam;

                    if ($key == '%v') { // skip values (raws, sub-statement etc)
                        // pass
                    } elseif ($key == '%n') { // escape ids/names
                        $value = $this->escapeIdentifier($value);
                    } else {
                        $value = $this->escape($value, strtr($key, ['%i' => '%d']));
                    }

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
     * @param  array  $whereParams
     * @return ?string
     */
    public final function where(string $where = null, array $whereParams = null): ?string
    {
        if (!empty($whereParams)) {
            $where = 'WHERE '. $this->prepare($where, $whereParams);
        }

        return $where;
    }

    /**
     * Prepare "LIMIT" statement.
     * @param  int|array $limit
     * @return ?string
     */
    public final function limit($limit): ?string
    {
        if (is_array($limit)) {
            return isset($limit[0], $limit[1])
                ? sprintf('LIMIT %d OFFSET %d', $limit[0], $limit[1])
                : sprintf('LIMIT %d', $limit[0]);
        }

        return ($limit || $limit === 0 || $limit === '0') ? 'LIMIT '. intval($limit) : null;
    }
}
