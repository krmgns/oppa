<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace Oppa\Agent;

use Oppa\{Config, Resource, Logger, Mapper, Profiler};
use Oppa\Batch\BatchInterface;
use Oppa\Query\Result\ResultInterface;
use Oppa\Exception\InvalidValueException;

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

        $resourceType = $this->resource->getType();
        $resourceObject = $this->resource->getObject();
        if ($resourceType == Resource::TYPE_MYSQL_LINK) {
            $result = $resourceObject->query('SHOW SESSION STATUS');
            while ($row = $result->fetch_assoc()) {
                $return[strtolower($row['Variable_name'])] = $row['Value'];
            }
            $result->free();
        } elseif ($resourceType == Resource::TYPE_PGSQL_LINK) {
            $result = pg_query($resourceObject, sprintf(
                "SELECT * FROM pg_stat_activity WHERE usename = '%s'",
                    pg_escape_string($resourceObject, $this->config['username'])
            ));
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
     * @return ?Oppa\Logger
     */
    public final function getLogger(): ?Logger
    {
        if (!$this->logger) {
            trigger_error("Logger is not found, did you set 'query_log' option as 'true'?");
        }

        return $this->logger;
    }

    /**
     * Get mapper.
     * @return ?Oppa\Mapper
     */
    public final function getMapper(): ?Mapper
    {
        if (!$this->mapper) {
            trigger_error("Mapper is not found, did you set 'map_result' option as 'true'?");
        }

        return $this->mapper;
    }

    /**
     * Get profiler.
     * @return ?Oppa\Profiler
     */
    public final function getProfiler(): ?Profiler
    {
        if (!$this->profiler) {
            trigger_error("Profiler is not found, did you set 'profile' option as 'true'?");
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
     * Where.
     * @param  string|array $where
     * @param  array        $whereParams
     * @param  string|null  $op
     * @return ?string
     */
    public final function where($where = null, array $whereParams = null, string $op = null): ?string
    {
        if ($where != null) {
            $whereType = gettype($where);
            if ($whereType == 'array') {
                $op = strtoupper($op ?: 'AND');
                if (!in_array($op, ['AND', 'OR'])) {
                    throw new InvalidValueException("Invalid operator '{$op}' given");
                }
                $where = join(' '. $op .' ', $where);
            } elseif ($whereType != 'string') {
                throw new InvalidValueException("Invalid where type '{$whereType}' given");
            }

            $where = ($whereParams != null) ? 'WHERE ('. $this->prepare($where, $whereParams) .')'
                : 'WHERE ('. $where .')';

        }

        return $where;
    }

    /**
     * Limit.
     * @param  int|array $limit
     * @return ?string
     */
    public final function limit($limit): ?string
    {
        if (is_array($limit)) {
            @ [$limit, $offset] = array_map('abs', $limit);

            return ($offset !== null) ? 'LIMIT '. $limit .' OFFSET '. $offset : 'LIMIT '. $limit;
        }

        if ($limit || $limit === 0 || $limit === '0') {
            return 'LIMIT '. abs($limit);

        }

        return null;
    }

    /**
     * Escape.
     * @param  any    $input
     * @param  string $inputFormat
     * @return any
     * @throws Oppa\Exception\InvalidValueException
     */
    public function escape($input, string $inputFormat = null)
    {
        $inputType = gettype($input);

        // in/not in statements
        if ($inputType == 'array') {
            return join(', ', array_map([$this, 'escape'], $input));
        }

        // escape strings %s and for all formattable types like %d, %f and %F
        if ($inputFormat && $inputFormat[0] == '%') {
            if ($inputFormat == '%s') {
                return $this->escapeString((string) $input);
            } elseif ($inputFormat == '%sl') {
                return $this->escapeLikeString((string) $input);
            } elseif ($inputFormat == '%b') {
                return $this->escapeBytea((string) $input);
            }
            return sprintf($inputFormat, $input);
        }

        switch ($inputType) {
            case 'NULL':
                return 'NULL';
            case 'string':
                return $this->escapeString($input);
            case 'integer':
                return $input;
            case 'boolean':
                return $input ? 'TRUE' : 'FALSE';
            case 'double':
                return sprintf('%F', $input); // %F = non-locale aware
            default:
                // no escape raws sql inputs like NOW(), ROUND(total) etc.
                if ($input instanceof Sql) {
                    return $input->toString();
                }

                throw new InvalidValueException("Unimplemented '{$inputType}' type encountered!");
        }

        return $input;
    }

    /**
     * Escape string.
     * @param  string $input
     * @param  bool   $quote
     * @return string
     * @throws Oppa\Agent\AgentException
     */
    public function escapeString(string $input, bool $quote = true): string
    {
        $resourceType = $this->resource->getType();
        if ($resourceType == Resource::TYPE_MYSQL_LINK) {
            $input = $this->resource->getObject()->real_escape_string($input);
        } elseif ($resourceType == Resource::TYPE_PGSQL_LINK) {
            $input = pg_escape_string($this->resource->getObject(), $input);
        } else {
            throw new AgentException('Cannot escape input, available for Mysql and Pgsql only!');
        }

        if ($quote) {
            $input = "'{$input}'";
        }

        return $input;
    }

    /**
     * Escape like string.
     * @param  string $input
     * @return string
     */
    public function escapeLikeString(string $input): string
    {
        return addcslashes($this->escapeString($input, false), '%_');
    }

    /**
     * Escape identifier.
     * @param  string|array $input
     * @return string
     * @throws Oppa\Agent\AgentException
     */
    public function escapeIdentifier($input): string
    {
        if ($input == '*') {
            return $input;
        }

        if (is_array($input)) {
            return join(', ', array_map([$this, 'escapeIdentifier'], $input));
        }

        if (is_string($input) && strpos($input, '.')) {
            return implode('.', array_map([$this, 'escapeIdentifier'], explode('.', $input)));
        }

        $resourceType = $this->resource->getType();
        if ($resourceType == Resource::TYPE_MYSQL_LINK) {
            $input = '`'. str_replace('`', '``', trim($input, '`')) .'`';
        } elseif ($resourceType == Resource::TYPE_PGSQL_LINK) {
            $input = pg_escape_identifier($this->resource->getObject(), trim($input, '"'));
        } else {
            throw new AgentException('Cannot escape identifier, available for Mysql and Pgsql only!');
        }

        return $input;
    }

    /**
     * Escape bytea.
     * @param  string $input
     * @return string
     * @throws Oppa\Agent\AgentException
     */
    public function escapeBytea(string $input): string
    {
        if ($this->resource->getType() != Resource::TYPE_PGSQL_LINK) {
            throw new AgentException('escapeBytea() available for only Pgsql!');
        }

        return pg_escape_bytea($this->resource->getObject(), $input);
    }

    /**
     * Unescape bytea.
     * @param  string $input
     * @return string
     * @throws Oppa\Agent\AgentException
     */
    public function unescapeBytea(string $input): string
    {
        if ($this->resource->getType() != Resource::TYPE_PGSQL_LINK) {
            throw new AgentException('unescapeBytea() available for only Pgsql!');
        }

        return pg_unescape_bytea($input);
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
     * @throws Oppa\Exception\InvalidValueException
     */
    public final function prepare(string $input, array $inputParams = null): string
    {
        $hasColon = strpos($input, ':') !== false;
        $hasFormat = strpbrk($input, '?%') !== false;
        if (($hasColon || $hasFormat) && ($inputParams === null || $inputParams === [])) {
            $inputParams = [null]; // fix missing replacement
        }

        if ($inputParams) {
            // available named word limits: :foo, :foo123, :foo_bar
            if ($hasColon) {
                preg_match_all('~(?<!:):([a-zA-Z0-9_]+)~', $input, $match);
                if (!empty($match[1])) {
                    $keys = $values = [];
                    $match[1] = array_unique($match[1]);
                    foreach ($match[1] as $key) {
                        if (!array_key_exists($key, $inputParams)) {
                            throw new InvalidValueException("Replacement key '{$key}' not found in params!");
                        }

                        $keys[] = sprintf('~:%s~', $key);
                        $values[] = $this->escape($inputParams[$key]);

                        // remove used params
                        unset($inputParams[$key]);
                    }
                    $input = preg_replace($keys, $values, $input);
                }
            }

            // available indicator: "?"
            // available operators with type definition: "%s, %i, %f, %v, %n, %sl"
            if ($hasFormat) {
                preg_match_all('~\?|%sl|%[sifvn]~', $input, $match);
                if (!empty($match[0])) {
                    foreach ($inputParams as $i => $inputParam) {
                        if (!array_key_exists($i, $match[0])) {
                            throw new InvalidValueException("Replacement index '{$i}' key not found in input!");
                        }

                        $key = $match[0][$i];
                        $value = $inputParam;

                        if ($key == '%v') {
                            // pass (values. raws, sub-query etc)
                        } elseif ($key == '%n') {
                            // identifiers (names)
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
        }

        return $input;
    }
}
