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
     * Where.
     * @param  string|array $where
     * @param  array        $whereParams
     * @param  string|null  $op
     * @return ?string
     * @throws Oppa\Agent\AgentException
     */
    public final function where($where = null, array $whereParams = null, string $op = null): ?string
    {
        if ($where != null) {
            $whereType = gettype($where);
            if ($whereType == 'array') {
                $op = strtoupper($op ?: 'AND');
                if (!in_array($op, ['AND', 'OR'])) {
                    throw new AgentException("Invalid operator '{$op}' given");
                }
                $where = join(' '. $op .' ', $where);
            } elseif ($whereType != 'string') {
                throw new AgentException("Invalid where type '{$whereType}' given");
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
        $input = $this->unquoteField($input);

        // nope.. quote all
        // if (ctype_lower($input)) {
        //     return $input;
        // }

        if ($this->isMysql()) {
            return '`'. str_replace('`', '``', $input) .'`';
        } elseif ($this->isPgsql()) {
            return '"'. str_replace('"', '""', $input) .'"';
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
     * @param  any    $input
     * @param  string $inputFormat
     * @param  bool   $quote
     * @return any
     * @throws Oppa\Agent\AgentException
     */
    public function escape($input, string $inputFormat = null, bool $quote = true)
    {
        $inputType = gettype($input);

        // in/not in statements
        if ($inputType == 'array') {
            return join(', ', array_map([$this, 'escape'], $input));
        }

        // escape strings %s and for all formattable types like %d, %f and %F
        if ($inputFormat && $inputFormat[0] == '%') {
            if ($inputFormat == '%s') {
                return $this->escapeString((string) $input, $quote);
            } elseif ($inputFormat == '%sl') {
                return $this->escapeLikeString((string) $input, $quote);
            } elseif ($inputFormat == '%b') {
                if (!is_bool($input)) {
                    throw new AgentException("Boolean types accepted only for %b operator, {$inputType} given!");
                }
                return $input ? 'TRUE' : 'FALSE';
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
                return $input ? 'TRUE' : 'FALSE';
            case 'double':
                return sprintf('%F', $input); // %F = non-locale aware
            default:
                // no escape raws sql inputs like NOW(), ROUND(total) etc.
                if ($input instanceof Sql) {
                    return $input->toString();
                }

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
     * @param  bool   $escape
     * @return string
     */
    public function escapeLikeString(string $input, bool $quote = true, bool $escape = true): string
    {
        if ($escape) {
            $input = $this->escapeString($input, $quote);
        }

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
            $input = array_map([$this, 'escapeIdentifier'], $input);

            return $join ? join(', ', $input) : $input;
        } elseif ($input instanceof Identifier) {
            $input = $input->toString();
        }

        if (!is_string($input)) {
            throw new AgentException(sprintf('String, array and Query\Sql type identifiers accepted only, %s given!',
                gettype($input)));
        }

        // functions, parentheses etc are not allowed
        if (strpos($input, '(') !== false) {
            throw new AgentException('Found parentheses in input, complex identifiers not allowed!');
        }

        // trim all non-word characters
        $input = preg_replace('~^[^\w]|[^\w]$~', '', $input);
        if ($input == '' || $input == '*') {
            return $input;
        }

        // multiple fields
        if (strpos($input, ',')) {
            return $this->escapeIdentifier(Util::split(',', $input), $join);
        }

        // aliases
        if (strpos($input, ' ')) {
            return preg_replace_callback('~([^\s]+)\s+(AS\s+)?(\w+)~i', function ($match) {
                return $this->escapeIdentifier($match[1]) . (
                    ($as = trim($match[2])) ? ' '. strtoupper($as) .' ' : ' '
                ) . $this->escapeIdentifier($match[3]);
            }, $input);
        }

        // dots
        if (strpos($input, '.')) {
            return implode('.', array_map([$this, 'escapeIdentifier'], explode('.', $input)));
        }

        return $this->quoteField($input);
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
     * @param  string        $input       Raw SQL complete/not complete.
     * @param  string|array  $inputParams Binding parameters.
     * @return string
     * @throws Oppa\Agent\AgentException
     */
    public final function prepare(string $input, $inputParams = null): string
    {
        $splitFlags = 2; // PREG_SPLIT_DELIM_CAPTURE

        // eg: ('@a.id ', '@b.id'), ('@a.id', '123'), ('@c.postId', '@b.id'), ('@a.id !=<> or none', '@b.id'), ...
        if ($input[0] == '@') {
            // eg: ('a.id', 'any...'), ('a.id !=<>', 'any...')
            // eg: ('a.id', '@any...'), ('a.id !=<>', '@any...')
            // eg: ('a.id', id(any...)), ('a.id !=<>', id(any...))
            if (is_string($inputParams)) {
                $inputParams = ($inputParams[0] == '@') ? $this->escapeIdentifier($inputParams)
                    : $this->escape($inputParams);
            } elseif ($inputParams instanceof Identifier) {
                $inputParams = $this->escapeIdentifier($inputParams);
            }

            if (!is_scalar($inputParams)) {
                throw new AgentException(sprintf('Scalar type replacement required, %s given!',
                    gettype($inputParams)));
            }

            [$field, $operator, $replaceOperator] = Util::split('~^@([\w\.]+)\s*([!]?=[<>]?)?\s*(.*)~',
                $input, $size=3, $splitFlags);
            if ($operator == null) {
                $operator = '='; // @default=equal
            }
            if ($replaceOperator != null) {
                $replaceOperator = trim((string) $replaceOperator);
                if ($replaceOperator == '%s') {
                    $inputParams = (string) (is_bool($inputParams) ? (int) $inputParams : $inputParams); // save for bools
                }
            }

            $field = $this->escapeIdentifier($field);
            if ($replaceOperator == '??' || $replaceOperator == '%n') {
                return sprintf('%s %s %s', $field, $operator, $this->escapeIdentifier($inputParams));
            }

            if ($replaceOperator && strpos($replaceOperator, '%') !== false) {
                return $this->prepare(($field . $operator . $replaceOperator), $inputParams);
            }

            if (is_bool($inputParams)) {
                return sprintf('%s %s %s', $field, $operator, $inputParams ? 'TRUE' : 'FALSE');
            }

            return sprintf('%s %s %s', $field, $operator, $replaceOperator ?: $inputParams);
        } elseif (is_string($inputParams) && $inputParams && $inputParams[0] == '@') {
            // eg: ('a.id', '@any...'), ('a.id !=<>', '@any...')
            [$field, $operator] = Util::split('~^([`"]?[\w\.]+[`"]?)\s*([!]?=|[<>]=?)\s*~', $input,
                $size=2, $splitFlags);
            if ($operator == null) {
                $operator = '='; // @default=equal
            }

            return sprintf('%s %s %s', $this->escapeIdentifier($field), $operator, $this->escapeIdentifier($inputParams));
        } /* elseif ($input != null && $inputParams !== null && !is_array($inputParams)) {
            // eg: ('id', any...), ('id !=<>', any...)
            [$field, $operator] = Util::split('~^([`"]?[\w\.]+[`"]?|$)\s*([!]?=|[<>]=?)\s*~', $input,
                $size=2, $splitFlags);
            if ($operator == null) {
                $operator = '='; // @default=equal
            }

            if (!is_scalar($inputParams)) {
                throw new AgentException(sprintf('Scalar type replacement required, %s given!',
                    gettype($inputParams)));
            }

            return sprintf('%s %s %s', $this->escapeIdentifier($field), $operator,
                ($inputParams instanceof Identifier) ? $this->escapeIdentifier($inputParams)
                    : $this->escape($inputParams));
        } */

        $inputParams = (array) $inputParams;
        if (empty($inputParams) || strpbrk($input, ':?%') === false) {
            return $input;
        }

        // available named word limits: :foo, :foo123, :foo_bar
        if (preg_match_all('~(?<!:):(\w+)~', $input, $match)) {
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

                    // remove used parameters
                    unset($inputParams[$key]);
                }
                $input = preg_replace($keys, $values, $input);
            }
        }

        // available indicator: "??, ?"
        // available operators with type definition: "%sl, %s, %i, %f, %F, %v, %n"
        if (preg_match_all('~\?\?|\?|%sl|%[sifFbvn]~', $input, $match)) {
            $operators = $match[0] ?? null;
            if ($operators != null) {
                $inputParams = array_values($inputParams);
                if (($operatorsCount = count($operators)) != ($inputParamsCount = count($inputParams))) {
                    throw new AgentException("Operators count not match with parameters count, ".
                        "(operators count: {$operatorsCount}, parameters count: {$inputParamsCount})!");
                }

                foreach ($inputParams as $i => $inputParam) {
                    if (!array_key_exists($i, $operators)) {
                        throw new AgentException("Replacement index '{$i}' key not found in input!");
                    }

                    $key = $operators[$i];
                    $value = $inputParam;

                    if ($key == '%v') {
                        // pass (values. raws, sub-query etc)
                    } elseif ($key == '%n' || $key == '??') {
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
