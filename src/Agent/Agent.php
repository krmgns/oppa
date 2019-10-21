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

use Oppa\{Util, Config, Resource, Logger, Mapper, Profiler};
use Oppa\Batch\BatchInterface;
use Oppa\Query\{Sql, Identifier};
use Oppa\Query\Result\ResultInterface;

/**
 * @package Oppa
 * @object  Oppa\Agent\Agent
 * @author  Kerem Güneş <k-gun@mail.com>
 */
abstract class Agent extends AgentCrud implements AgentInterface
{
    /**
     * Name.
     * @var string
     */
    protected $name;

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
     * Constructor.
     * @param  Oppa\Config $config
     */
    public final function __construct(Config $config)
    {
        $this->init($config);

        $this->name = strtolower(substr(static::class, strrpos(static::class, '\\') + 1));
    }

    /**
     * Destructor.
     */
    public final function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Is mysql.
     * @return bool
     */
    public final function isMysql(): bool
    {
        return $this->name == 'mysql';
    }

    /**
     * Is pgsql.
     * @return bool
     */
    public final function isPgsql(): bool
    {
        return $this->name == 'pgsql';
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
        if ($this->logger == null) {
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
        if ($this->mapper == null) {
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
        if ($this->profiler == null) {
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
        return $this->name;
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
     * Count.
     * @param  string            $table
     * @param  string|array|null $where
     * @param  any|null          $whereParams
     * @param  string|null       $whereOp
     * @param  int|null          $limit
     * @return ?int
     */
    public function count(string $table, $where = null, $whereParams = null, string $whereOp = null,
        int $limit = null): ?int
    {
        $query = $this->prepare('SELECT count(*) AS count FROM %n %r', [
            $table, $this->where($where, $whereParams, $whereOp)
        ]);

        if ($limit != null) {
            $query = $query .' '. $this->limit($limit);
        }

        $result = (object) $this->get($query);

        return isset($result->count) ? intval($result->count) : null;
    }

    /**
     * Count query.
     * @param  string $query
     * @return ?int
     */
    public function countQuery(string $query): ?int
    {
        $query = sprintf("SELECT count(*) AS count FROM (\n\t%s\n) AS tmp", trim($query));

        $result = (object) $this->get($query);

        return isset($result->count) ? intval($result->count) : null;
    }

    /**
     * Where.
     * @param  string|array $where
     * @param  any|null     $whereParams
     * @param  string|null  $op
     * @return ?string
     * @throws Oppa\Agent\AgentException
     */
    public final function where($where, $whereParams = null, string $op = null): ?string
    {
        if ($where != null) {
            if (is_array($where)) {
                $op = strtoupper($op ?: 'AND');
                if (!in_array($op, ['AND', 'OR'])) {
                    throw new AgentException("Invalid operator '{$op}' given");
                }
                $where = join(' '. $op .' ', $where);
            }

            if (!is_string($where)) {
                throw new AgentException(sprintf("Invalid where type '%s' given", gettype($where)));
            }

            $where = ($whereParams !== null) ? 'WHERE ('. $this->prepare($where, $whereParams) .')'
                : 'WHERE ('. $where .')';

        }

        return $where;
    }

    /**
     * Order.
     * @param  string|array $order
     * @return ?string
     */
    public final function order($order): ?string
    {
        if ($order != null) {
            if (is_array($order)) {
                @ [$field, $op] = $order;

                return trim('ORDER BY '. $this->escapeIdentifier($field) .' '. strtoupper($op ?: ''));
            }

            return 'ORDER BY '. $this->escapeIdentifier($order);
        }

        return null;
    }

    /**
     * Limit.
     * @param  int|array $limit
     * @return ?string
     */
    public final function limit($limit): ?string
    {
        if ($limit != null) {
            if (is_array($limit)) {
                @ [$limit, $offset] = array_map('abs', $limit);

                return ($offset !== null) ? 'LIMIT '. $limit .' OFFSET '. $offset : 'LIMIT '. $limit;
            }

            return 'LIMIT '. abs($limit);
        }

        return null;
    }

    /**
     * Quote.
     * @param  string $input
     * @return string
     */
    public function quote(string $input): string
    {
        return "'". $this->unquote($input) ."'";
    }

    /**
     * Unquote.
     * @param  string $input
     * @return string
     */
    public function unquote(string $input): string
    {
        return trim($input, "'\\");
    }

    /**
     * Quote field.
     * @param  string $input
     * @return string
     * @throws Oppa\Agent\AgentException
     */
    public function quoteField(string $input): string
    {
        // nope.. quote all
        // if (ctype_lower($input)) {
        //     return $input;
        // }

        if ($this->isMysql()) {
            return '`'. str_replace('`', '``', $this->unquoteField($input)) .'`';
        } elseif ($this->isPgsql()) {
            return '"'. str_replace('"', '""', $this->unquoteField($input)) .'"';
        }
    }

    /**
     * Unquote field.
     * @param  string $input
     * @return string
     */
    public function unquoteField(string $input): string
    {
        return trim($input, ' `"\\');
    }

    /**
     * Escape.
     * @param  any         $input
     * @param  string|null $inputFormat
     * @param  bool        $quote
     * @return any
     * @throws Oppa\Agent\AgentException
     */
    public function escape($input, string $inputFormat = null, bool $quote = true)
    {
        // no escape raws sql inputs like NOW(), ROUND(total) etc
        if ($input instanceof Sql) {
            return $input->toString();
        } elseif ($input instanceof Identifier) {
            return $this->escapeIdentifier($input);
        }

        $inputType = gettype($input);

        // in/not in statements
        if ($inputType == 'array') {
            return join(', ', array_map([$this, 'escape'], $input));
        }

        // escape strings %s and for all formattable types like %d, %f and %F
        if ($inputFormat != null && $inputFormat[0] == '%') {
            if ($inputFormat == '%s') {
                return $this->escapeString((string) $input, $quote);
            } elseif ($inputFormat == '%sl') {
                return $this->escapeLikeString((string) $input, $quote);
            } elseif ($inputFormat == '%b') {
                if (!is_bool($input)) {
                    throw new AgentException("Bool types accepted only for %b operator, {$inputType} given!");
                }
                return $input ? 'true' : 'false';
            }

            if (is_string($input) && in_array($inputFormat, ['%d', '%i', '%f', '%F'])) {
                $input = $this->unquote($input);
            }

            return sprintf($inputFormat, $input);
        }

        switch ($inputType) {
            case 'NULL':
                return 'NULL';
            case 'string':
                return $this->escapeString($input, $quote);
            case 'integer':
                return $input;
            case 'boolean':
                return $input ? 'true' : 'false';
            case 'double':
                return sprintf('%F', $input); // %F = non-locale aware
            default:
                throw new AgentException("Unimplemented '{$inputType}' type encountered!");
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
        $resourceObject = $this->resource->getObject();
        if ($resourceType == Resource::TYPE_MYSQL_LINK) {
            $input = $resourceObject->escape_string($input);
        } elseif ($resourceType == Resource::TYPE_PGSQL_LINK) {
            $input = pg_escape_string($resourceObject, $input);
        }

        if ($quote) {
            $input = $this->quote($input);
        }

        return $input;
    }

    /**
     * Escape like string.
     * @param  string $input
     * @param  bool   $quote
     * @return string
     */
    public function escapeLikeString(string $input, bool $quote = true): string
    {
        $input = $this->escapeString($input, $quote);

        return addcslashes($input, '%_');
    }

    /**
     * Escape identifier.
     * @param  string|array|Oppa\Query\Identifier $input
     * @param  bool                               $join
     * @return string|array
     * @throws Oppa\Agent\AgentException
     */
    public function escapeIdentifier($input, bool $join = true)
    {
        if (is_array($input)) {
            $input = array_filter($input);
            foreach ($input as $i => $name) {
                $input[$i] = $this->escapeIdentifier($name);
            }
            return $join ? join(', ', $input) : $input;
        } elseif ($input instanceof Sql) {
            return $input->toString();
        } elseif ($input instanceof Identifier) {
            $input = $input->toString();
        }

        if (!is_string($input)) {
            throw new AgentException(sprintf('String, array and Query\Sql,Identifier type identifiers'.
                ' accepted only, %s given!', gettype($input)));
        } elseif (strpos($input, '(') !== false) { // functions, parentheses etc not allowed
            throw new AgentException('Found parentheses in input, complex identifiers not allowed!');
        }

        $input = trim($input);
        if ($input == '' || $input == '*') {
            return $input;
        }

        // trim all non-word (and non-bracket) characters
        $input = preg_replace('~^[^\w\[\]]|[^\w\[\]\*]$~', '', $input);
        if ($input == '') {
            return $input;
        }

        // commas (multiple names)
        if (strpos($input, ',')) {
            return implode(', ', array_map([$this, 'escapeIdentifier'], explode(',', $input)));
        }

        // dots (sub-names)
        if (strpos($input, '.')) {
            return implode('.', array_map([$this, 'escapeIdentifier'], explode('.', $input)));
        }

        // aliases (AS)
        if (strpos($input, ' ')) {
            return preg_replace_callback('~([^\s]+)\s+(AS\s+)?([^\s]+)~i', function ($match) {
                return $this->escapeIdentifier($match[1]) . (
                    ($as = trim($match[2])) ? ' '. strtoupper($as) .' ' : ' '
                ) . $this->escapeIdentifier($match[3]);
            }, $input);
        }

        // arrays
        $array = null;
        if ($arrayPos = strpos($input, '[')) {
            $array = substr($input, $arrayPos);
            $input = substr($input, 0, $arrayPos);
        }

        $input = $this->quoteField($input);
        if ($array != null) {
            $input .= $array; // append back
        }

        return $input;
    }

    /**
     * Escape name.
     * @alias of escapeIdentifier()
     */
    public function escapeName($input, bool $join = true)
    {
        return $this->escapeIdentifier($input, $join);
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
     * @param  any    $inputParams Binding parameters.
     * @return string
     * @throws Oppa\Agent\AgentException
     */
    public final function prepare(string $input, $inputParams = null): string
    {
        $splitFlags = 2; // delim capture

        // eg: ('@id = ? AND @name = ?', ['1', 'foo'])
        if (strstr($input, '@')) {
            $input = preg_replace_callback('~@([\w\.\[\]]+)~', function ($match) {
                return $this->escapeIdentifier($match[1]);
            }, $input);
        }

        // 'null' not replaced, gives error, and Sql/Identifier objects issue
        $inputParams = (array) (
            !is_null($inputParams) && !is_object($inputParams) ? $inputParams : [$inputParams]
        );
        if (empty($inputParams) || strpbrk($input, ':?%') === false) {
            return $input;
        }

        // available named operators: :foo, :foo123, :foo_bar (but not 'x::int')
        if (preg_match_all('~(?<![:]):(\w+)~', $input, $match)) {
            $operators = $match[1] ?? null;
            if ($operators != null) {
                $keys = $values = [];
                $operators = array_unique($operators);
                foreach ($operators as $key) {
                    if (!array_key_exists($key, $inputParams)) {
                        throw new AgentException("Replacement key '{$key}' not found in parameters!");
                    }

                    $keys[] = sprintf('~:%s~', $key);
                    $values[] = $this->escape($inputParams[$key]);

                    unset($inputParams[$key]); // drop used parameters
                }
                $input = preg_replace($keys, $values, $input);
            }
        }

        // available operators with type: %sl, %s, %i, %f, %F, %n, %r (or ?sl, ?s, ?i, ?f, ?F, ?n, ?r)
        // available operators: ??, ? (but not 'x::jsonb ?| array...')
        if (preg_match_all('~[%?]sl|[%?][sifFbnr](?![\w])|\?\?|\?(?![|])~', $input, $match)) {
            $operators = $match[0] ?? null;
            if ($operators != null) {
                $inputParams = array_values($inputParams);
                if (($operatorsCount = count($operators)) != ($inputParamsCount = count($inputParams))) {
                    throw new AgentException("Formats count not match with parameters count, ".
                        "(operators count: {$operatorsCount}, parameters count: {$inputParamsCount})!");
                }

                foreach ($inputParams as $i => $inputParam) {
                    if (!array_key_exists($i, $operators)) {
                        throw new AgentException("Replacement index '{$i}' key not found in input!");
                    }

                    $key = $operators[$i];
                    $value = $inputParam;

                    if ($key == '%r' || $key == '?r') {
                        // pass raw & sub-query etc (values)
                    } elseif ($key == '%n' || $key == '?n' || $key == '??') {
                        // identifiers (names)
                        $value = $this->escapeIdentifier($value);
                    } else {
                        $format = $key;
                        if (isset($key[1]) && $key[1] != '?') {
                            $format = str_replace('?', '%', $key);
                        }
                        $value = $this->escape($value, strtr($format, ['%i' => '%d']));
                    }

                    if (false !== ($keyPos = strpos($input, $key))) {
                        $input = substr_replace($input, $value, $keyPos, strlen($key));
                    }
                }
            }
        }

        return $input;
    }

    /**
     * Prepare identifier.
     * @param  string       $input
     * @param  string|array $inputParam
     * @return string
     */
    public final function prepareIdentifier(string $input, $inputParam): string
    {
        if (empty($inputParam)) {
            if (strpos($input, '%n') !== false) {
                throw new AgentException('Found %n operator but no replacement parameter given!');
            } elseif (strpos($input, '??') !== false) {
                throw new AgentException('Found ?? operator but no replacement parameter given!');
            }
        } else {
            $input = $this->prepare($input, (array) $inputParam);

            // eg: ('@a.id=?', 1), ('@a.id=@b.id')
            // eg: ('??=?', ['a.id',1]), ('??=??', ['a.id','b.id'])
            // eg: ('a.id=?, a.b=?', [1,2]), ('a.id=??, a.b=?', ['b.id',2])
            if (strpos($input, '=')) {
                $input = implode(', ', array_map(function ($input) {
                    $input = trim($input);
                    if ($input != '') {
                        $input = array_map('trim', explode('=', $input));
                        $input = $this->escapeIdentifier($input[0]) .' = '. (
                            ($input[1] && $input[1][0] == '@' )
                                ? $this->escapeIdentifier($input[1]) : $input[1]
                        );
                        return $input;
                    }
                }, explode(',', $input)));
            }

            return $input;
        }

        // eg: (a.id = 1), (a.id = @b.id)
        if (strpos($input, '=')) {
            $input = implode(', ', array_map(function ($input) {
                $input = trim($input);
                if ($input != '') {
                    $input = array_map('trim', explode('=', $input));
                    $input = $this->escapeIdentifier($input[0]) .' = '. (
                        ($input[1] && $input[1][0] == '@' )
                            ? $this->escapeIdentifier($input[1]) : $this->escape($input[1])
                    );
                    return $input;
                }
            }, explode(',', $input)));
        } else {
            // eg: a.id
            $input = $this->escapeIdentifier($input);
        }

        return $input;
    }
}
