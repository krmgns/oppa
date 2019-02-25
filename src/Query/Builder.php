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

namespace Oppa\Query;

use Oppa\Util;
use Oppa\Link\Link;
use Oppa\Agent\AgentInterface;
use Oppa\Query\Result\ResultInterface;

/**
 * @package Oppa
 * @object  Oppa\Query\Builder
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Builder
{
    /**
     * And/or operators.
     * @const string
     */
    public const OP_OR       = 'OR',
                 OP_AND      = 'AND';

    /**
     * Asc/desc operators.
     * @const string
     */
    public const OP_ASC      = 'ASC',
                 OP_DESC     = 'DESC';

    /**
     * Link.
     * @var Oppa\Link\Link
     */
    private $link;

    /**
     * Agent.
     * @var Oppa\Agent\AgentI
     */
    private $agent;

    /**
     * Table.
     * @var string
     */
    private $table;

    /**
     * Query.
     * @var array
     */
    private $query = [];

    /**
     * Constructor.
     * @param Oppa\Link\Link|null $link
     * @param string|null         $table
     */
    public function __construct(Link $link = null, string $table = null)
    {
        $link && $this->setLink($link);
        $table && $this->setTable($table);
    }

    /**
     * Stringer.
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Set link.
     * @param  Oppa\Link\Link $link
     * @return self
     */
    public function setLink(Link $link): self
    {
        $this->link = $link;
        $this->agent = $link->getAgent();

        return $this;
    }

    /**
     * Get link.
     * @return ?Oppa\Link\Link
     */
    public function getLink(): ?Link
    {
        return $this->link;
    }

    /**
     * Get agent.
     * @return ?Oppa\Agent\AgentInterface
     */
    public function getAgent(): ?AgentInterface
    {
        return $this->agent;
    }

    /**
     * Set table.
     * @param  string $table
     * @return self
     */
    public function setTable(string $table): self
    {
        $this->table = $this->agent->escapeIdentifier($table);

        return $this;
    }

    /**
     * Get table.
     * @return ?string
     */
    public function getTable(): ?string
    {
        return $this->table;
    }

    /**
     * Reset.
     * @return self
     */
    public function reset(): self
    {
        $this->query = [];

        return $this;
    }

    /**
     * Has.
     * @param  string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return !empty($this->query[$key]);
    }

    /**
     * Select.
     * @param  string|Builder $field
     * @param  string         $as
     * @param  bool           $esc
     * @param  bool           $reset
     * @return self
     * @throws Oppa\Query\BuilderException
     */
    public function select($field = null, string $as = null, bool $esc = true, bool $reset = true): self
    {
        $reset && $this->reset();

        // handle other query object
        if ($field instanceof Builder) {
            if ($as == null) {
                throw new BuilderException('Alias required!');
            }

            return $this->push('select', '('. $field->toString() .') AS '. $as);
        }

        if ($field === '1') {
            return $this->push('select', $field);
        }

        $field = $this->field($field, $esc);
        if ($field == '') {
            $field = '1'; // pass for aggregate method, e.g select().aggregate('count', 'id')
        } else {
            $field = trim($field, ', ');
        }

        if ($as != null) {
            $field = $field .' AS '. $as;
        }

        return $this->push('select', $field);
    }

    /**
     * Select more.
     * @param  string|Builder $field
     * @param  string|null    $as
     * @param  bool           $esc
     * @return self
     */
    public function selectMore($field, string $as = null, bool $esc = true): self
    {
        return $this->select($field, $as, $esc, false);
    }

    /**
     * Select json.
     * @param  string|array $field
     * @param  string       $as
     * @param  string       $type
     * @param  bool         $esc
     * @param  bool         $reset
     * @return self
     * @throws Oppa\Query\BuilderException
     */
    public function selectJson($field, string $as, string $type = 'object', bool $esc = true, bool $reset = true): self
    {
        static $server, $serverVersion, $serverVersionMin, $jsonObject, $jsonArray;

        if ($server == null) {
            if ($this->agent->isMysql()) {
                $server = 'MySQL'; $serverVersionMin = '5.7.8';
                $jsonObject = 'json_object'; $jsonArray = 'json_array';
            } elseif ($this->agent->isPgsql()) {
                $server = 'PostgreSQL'; $serverVersionMin = '9.4';
                $jsonObject = 'json_build_object'; $jsonArray = 'json_build_array';
            }

            $serverVersion = $this->link->getDatabase()->getInfo('serverVersion');
            if (version_compare($serverVersion, $serverVersionMin) == -1) {
                throw new BuilderException(sprintf('JSON not supported by %s/v%s, minimum v%s required',
                    $server, $serverVersion, $serverVersionMin));
            }
        }

        $json = [];
        if (is_string($field)) {
            foreach (Util::split('\s*,\s*', $field) as $field) {
                if ($type == 'object') {
                    @ [$key, $value] = Util::split('\s*:\s*', $field);
                    if (!isset($key, $value)) {
                        throw new BuilderException('Field name and value must be given fo JSON objects!');
                    }
                    $json[] = $this->agent->quote(trim($key));
                    if ($esc || strpos($value, '.')) {
                        $value = $this->agent->escapeIdentifier($value);
                    }
                    $json[] = $value;
                } elseif ($type == 'array') {
                    if (!isset($key)) {
                        throw new BuilderException('Field value must be given fo JSON arrays!');
                    }
                    $json[] = $this->agent->quote(trim($key));
                }
            }
        } elseif (is_array($field)) {
            foreach ($field as $key => $value) {
                $keyType = gettype($key);
                if ($type == 'object') {
                    if ($keyType != 'string') {
                        throw new BuilderException(sprintf('Field name must be string, %s given !', $keyType));
                    }
                    $json[] = $this->agent->quote($key) .', '. ($esc ? $this->agent->quoteField($value) : $value);
                } elseif ($type == 'array') {
                    if ($keyType != 'integer') {
                        throw new BuilderException(sprintf('Field name must be int, %s given !', $keyType));
                    }
                    $json[] = $esc ? $this->agent->quoteField($value) : $value;
                }
            }
        } else {
            throw new BuilderException(sprintf('String and array fields accepted only, %s given',
                gettype($field)));
        }

        if ($type == 'object') {
            $json = sprintf('%s(%s) AS %s', $jsonObject, join(', ', $json), $as);
        } elseif ($type == 'array') {
            $json = sprintf('%s(%s) AS %s', $jsonArray, join(', ', $json), $as);
        } else {
            throw new BuilderException("Given JSON type '{$type}' is not implemented!");
        }

        return $this->select($json, null, false, $reset);
    }

    /**
     * Select json more.
     * @param  string|array $field
     * @param  string       $as
     * @param  string       $type
     * @param  bool         $esc
     * @return self
     */
    public function selectJsonMore($field, string $as, string $type = 'object', bool $esc = true): self
    {
        return $this->selectJson($field, $as, $type, $esc, false);
    }

    /**
     * From
     * @param  string|Builder $field
     * @param  string|null    $as
     * @return self
     */
    public function from($field, string $as = null): self
    {
        $from = $this->field($field);
        if ($as != null) {
            $from = sprintf('(%s) AS %s', $from, $as);
        }

        $this->query['from'] = $from;

        return $this;
    }

    /**
     * Insert.
     * @param  array $data
     * @return self
     */
    public function insert(array $data): self
    {
        $this->reset();
        // simply check is not assoc to prepare multi-insert
        if (!isset($data[0])) {
            $data = [$data];
        }

        return $this->push('insert', $data);
    }

    /**
     * Update.
     * @param  array $data
     * @return self
     */
    public function update(array $data): self
    {
        $this->reset();

        return $this->push('update', $data);
    }

    /**
     * Delete.
     * @return self
     */
    public function delete(): self
    {
        $this->reset();

        return $this->push('delete', true);
    }

    /**
     * Join.
     * @param  string $table
     * @param  string $on
     * @param  array  $params
     * @return self
     */
    public function join(string $table, string $on, array $params = null): self
    {
        if ($params != null) {
            $on = $this->agent->prepare($on, $params);
        }

        return $this->push('join', sprintf('JOIN %s ON (%s)', $table, $on));
    }

    /**
     * Join using.
     * @param  string $table
     * @param  string $using
     * @param  array  $params
     * @return self
     */
    public function joinUsing(string $table, string $using, array $params = null): self
    {
        if ($params != null) {
            $using = $this->agent->prepare($using, $params);
        }

        return $this->push('join', sprintf('JOIN %s USING (%s)', $table, $using));
    }

    /**
     * Left join.
     * @param  string $table
     * @param  string $on
     * @param  array  $params
     * @return self
     */
    public function joinLeft(string $table, string $on, array $params = null): self
    {
        if ($params != null) {
            $on = $this->agent->prepare($on, $params);
        }

        return $this->push('join', sprintf('LEFT JOIN %s ON (%s)', $table, $on));
    }

    /**
     * Left join using.
     * @param  string $table
     * @param  string $using
     * @param  array  $params
     * @return self
     */
    public function joinLeftUsing(string $table, string $using, array $params = null): self
    {
        if ($params != null) {
            $using = $this->agent->prepare($using, $params);
        }

        return $this->push('join', sprintf('LEFT JOIN %s USING (%s)', $table, $using));
    }

    /**
     * Where.
     * @param  string|array|Builder $query
     * @param  any                  $queryParams
     * @param  string               $op
     * @return self
     */
    public function where($query, $queryParams = null, string $op = self::OP_AND): self
    {
        // sub-where
        if ($queryParams instanceof Builder) {
            // $opr argument is empty, should be exists in query (eg: id = )
            $query = $this->prepare($query, '', $queryParams);

            return $this->push('where', $query);
        }

        if ($query && is_array($query)) {
            $queryParams = (array) $queryParams;
            if ($queryParams == null) {
                throw new BuilderException('Both query and parameters required');
            }

            $isSequential = isset($query[0]);
            // eg: where(['id', '='], 1)
            // eg: where(['id', '=', '%i'], 1)
            if ($isSequential) {
                @ [$field, $operator, $escaper] = $query;
                $query = sprintf('%s %s %s', $this->field($field), $operator, $escaper ?: '?');
            }
        }

        if (!is_string($query)) {
            throw new BuilderException(sprintf('String, array or Builder type queries are accepted only, %s given',
                gettype($query)));
        }

        if ($queryParams !== null) {
            if (!is_array($queryParams) && !is_scalar($queryParams)) {
                throw new BuilderException(sprintf('Array or scalar parameters are accepted only, %s given',
                    gettype($queryParams)));
            }
        }

        if ($op == '') {
            throw new BuilderException('No OR/AND op given');
        }

        $query = $this->agent->prepare($query, (array) $queryParams);

        return $this->push('where', [[$query, $op]]);
    }

    /**
     * Where equal.
     * @param  string|array|Builder $field
     * @param  any                  $param
     * @param  string               $op
     * @return self
     */
    public function whereEqual($field, $param, string $op = self::OP_AND): self
    {
        return $this->where($this->field($field) .' = ?', $param, $op);
    }

    /**
     * Where not equal.
     * @param  string|array|Builder $field
     * @param  any                  $param
     * @param  string               $op
     * @return self
     */
    public function whereNotEqual($field, $param, string $op = self::OP_AND): self
    {
        return $this->where($this->field($field) .' != ?', $param, $op);
    }

    /**
     * Where null.
     * @param  string|array|Builder $field
     * @param  string               $op
     * @return self
     */
    public function whereNull($field, string $op = self::OP_AND): self
    {
        return $this->where($this->field($field) .' IS NULL', null, $op);
    }

    /**
     * Where not null.
     * @param  string|array|Builder $field
     * @param  string               $op
     * @return self
     */
    public function whereNotNull($field, string $op = self::OP_AND): self
    {
        return $this->where($this->field($field) .' IS NOT NULL', null, $op);
    }

    /**
     * Where in.
     * @param  string|array|Builder $field
     * @param  array|Builder        $param
     * @param  string               $op
     * @return self
     */
    public function whereIn($field, $param, string $op = self::OP_AND): self
    {
        if (is_array($param)) {
            $param = [$param];
        }

        return $this->where($this->field($field) .' IN (?)', $param, $op);
    }

    /**
     * Where not in.
     * @param  string|array|Builder $field
     * @param  array|Builder        $param
     * @param  string               $op
     * @return self
     */
    public function whereNotIn($field, $param, string $op = self::OP_AND): self
    {
        if (is_array($param)) {
            $param = [$param];
        }

        return $this->where($this->field($field) .' NOT IN (?)', $param, $op);
    }

    /**
     * Where between.
     * @param  string|array|Builder $field
     * @param  array                $params
     * @param  string               $op
     * @return self
     */
    public function whereBetween($field, array $params, string $op = self::OP_AND): self
    {
        return $this->where($this->field($field) .' BETWEEN (? AND ?)', $params, $op);
    }

    /**
     * Where not between.
     * @param  string|array|Builder $field
     * @param  array                $params
     * @param  string               $op
     * @return self
     */
    public function whereNotBetween($field, array $params, string $op = self::OP_AND): self
    {
        return $this->where($this->field($field) .' NOT BETWEEN (? AND ?)', $params, $op);
    }

    /**
     * Where less than.
     * @param  string|array|Builder $field
     * @param  any                  $param
     * @param  string               $op
     * @return self
     */
    public function whereLessThan($field, $param, string $op = self::OP_AND): self
    {
        return $this->where($this->field($field) .' < ?', $param, $op);
    }

    /**
     * Where less than equal.
     * @param  string|array|Builder $field
     * @param  any                  $param
     * @param  string               $op
     * @return self
     */
    public function whereLessThanEqual($field, $param, string $op = self::OP_AND): self
    {
        return $this->where($this->field($field) .' <= ?', $param, $op);
    }

    /**
     * Where greater than.
     * @param  string|array|Builder $field
     * @param  any                  $param
     * @param  string               $op
     * @return self
     */
    public function whereGreaterThan($field, $param, string $op = self::OP_AND): self
    {
        return $this->where($this->field($field) .' > ?', $param, $op);
    }

    /**
     * Where greater than equal.
     * @param  string|array|Builder $field
     * @param  any                  $param
     * @param  string               $op
     * @return self
     */
    public function whereGreaterThanEqual($field, $param, string $op = self::OP_AND): self
    {
        return $this->where($this->field($field) .' >= ?', $param, $op);
    }

    /**
     * Where like.
     * @param  string|array|Builder $field
     * @param  string               $format
     * @param  string|null          $param
     * @param  string               $op
     * @return self
     */
    public function whereLike($field, string $format = '', string $param = null, string $op = self::OP_AND): self
    {
        @ [$start, $end] = explode('-', $format);
        // 'foo%'  Anything starts with "foo"
        // '%foo'  Anything ends with "foo"
        // '%foo%' Anything have "foo" in any position
        // 'f_o%'  Anything have "o" in the second position
        // 'f_%_%' Anything starts with "f" and are at least 3 characters in length
        // 'f%o'   Anything starts with "f" and ends with "o"
        return $this->where($this->field($field) ." LIKE '{$end}%sl{$start}'", $param, $op);
    }

    /**
     * Where like start.
     * @param  string|array|Builder $field
     * @param  string               $param
     * @param  string               $op
     * @return self
     */
    public function whereLikeStart($field, string $param, string $op = self::OP_AND): self
    {
        return $this->whereLike($field, '%-', $param, $op);
    }

    /**
     * Where like end.
     * @param  string|array|Builder $field
     * @param  string               $param
     * @param  string               $op
     * @return self
     */
    public function whereLikeEnd($field, string $param, string $op = self::OP_AND): self
    {
        return $this->whereLike($field, '-%', $param, $op);
    }

    /**
     * Where like both.
     * @param  string|array|Builder $field
     * @param  string               $param
     * @param  string               $op
     * @return self
     */
    public function whereLikeBoth($field, string $param, string $op = self::OP_AND): self
    {
        return $this->whereLike($field, '%-%', $param, $op);
    }

    /**
     * Where exists.
     * @param  any    $query
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    public function whereExists($query, array $params = null, string $op = self::OP_AND): self
    {
        if ($query instanceof Builder) {
            $query = $query->toString();
        }

        if ($params != null) {
            $query = $this->agent->prepare($query, $params);
        }

        return $this->where('EXISTS ('. $query .')', null, $op);
    }

    /**
     * Where not exists.
     * @param  any    $query
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    public function whereNotExists($query, array $params = null, string $op = self::OP_AND): self
    {
        if ($query instanceof Builder) {
            $query = $query->toString();
        }

        if ($params != null) {
            $query = $this->agent->prepare($query, $params);
        }

        return $this->where('NOT EXISTS ('. $query .')', null, $op);
    }

    /**
     * Where id.
     * @param  string     $field
     * @param  int|string $id
     * @param  string     $op
     * @return self
     */
    public function whereId(string $field, $id, string $op = self::OP_AND): self
    {
        if (is_array($id)) {
            $id = array_shift($id);
        }

        return $this->where('?? = ?', [$field, $id], $op);
    }

    /**
     * Where ids.
     * @param  string            $field
     * @param  array[int|string] $ids
     * @param  string            $op
     * @return self
     */
    public function whereIds(string $field, array $ids, string $op = self::OP_AND): self
    {
        return $this->where('?? IN (?)', [$field, $ids], $op);
    }

    /**
     * Id (alias of whereId()).
     * @param  int|string $id
     * @param  string     $op
     * @return self
     */
    public function id($id, string $op = self::OP_AND): self
    {
        return $this->whereId('id', $id, $op);
    }

    /**
     * Ids (alias of whereIds()).
     * @param  int|string $ids
     * @param  string     $op
     * @return self
     */
    public function ids($ids, string $op = self::OP_AND): self
    {
        return $this->whereIds('id', [$ids], $op);
    }

    /**
     * Where match against.
     * @param  string|array|Builder $field
     * @param  array                $params
     * @param  bool                 $isPhrase
     * @param  string               $mode
     * @return self
     */
    public function whereMatchAgainst($field, array $params, bool $isPhrase = false, string $mode = ''): self
    {
        // pre($this->agent->getName());
        $against = [];
        foreach ($params as $param) {
            $param = (string) $param;
            $operator = '';
            if (strpbrk($param[0], '+-*~')) {
                $operator = $param[0];
                $param = substr($param, 1); // bump operator
            }

            $param = $this->agent->escapeString($param, false);
            if ($isPhrase) {
                $param = '"'. trim($param, '\\"') .'"';
            }

            // add operator if exists
            $against[] = $operator . $param;
        }

        $against = join(' ', $against);
        $mode = $mode ?: 'IN BOOLEAN MODE';

        return $this->where("match(". $this->field($field) .") against('{$against}' {$mode})");
    }

    /**
     * Having.
     * @param  string $query
     * @param  array  $params
     * @param  string $op
     * @return self
     */
    public function having(string $query, array $params = null, string $op = self::OP_AND): self
    {
        if ($params != null) {
            $query = $this->agent->prepare($query, $params);
        }

        // add and/or operator
        if (!empty($this->query['having'])) {
            $query = sprintf('%s %s', $op, $query);
        }

        return $this->push('having', $query);
    }

    /**
     * Group by.
     * @param  string|array|Builder $field
     * @return self
     */
    public function groupBy($field): self
    {
        return $this->push('groupBy', $this->field($field));
    }

    /**
     * Order by.
     * @param  string|array|Builder $field
     * @param  string               $op
     * @return self
     * @throws Oppa\Query\BuilderException
     */
    public function orderBy($field, string $op = null): self
    {
        // check operator is valid
        if ($op == null) {
            return $this->push('orderBy', $this->field($field));
        }

        $op = strtoupper($op);
        if ($op != self::OP_ASC && $op != self::OP_DESC) {
            throw new BuilderException('Available ops: ASC, DESC');
        }

        return $this->push('orderBy', $this->field($field) .' '. $op);

    }

    /**
     * Limit.
     * @param  int      $limit
     * @param  int|null $offset
     * @return self
     */
    public function limit(int $limit, int $offset = null): self
    {
        $this->query['limit'] = ($offset === null)
            ? [abs($limit)] : [abs($limit), abs($offset)];

        return $this;
    }

    /**
     * Offset.
     * @param  int $offset
     * @return self
     */
    public function offset(int $offset): self
    {
        if (!isset($this->query['limit'][0])) {
            throw new BuilderException('Limit not set yet, call limit() first');
        }

        $this->query['limit'][1] = abs($offset);

        return $this;
    }

    /**
     * Aggregate.
     * @param  string      $fn
     * @param  string      $field
     * @param  string|null $as
     * @return self
     * @throws Oppa\Query\BuilderException
     */
    public function aggregate(string $fn, string $field = '*', string $as = null): self
    {
        static $fns = ['count', 'sum', 'avg', 'min', 'max'];

        $fn = strtolower($fn);
        if (!in_array($fn, $fns)) {
            throw new BuilderException(sprintf('Invalid function %s given, %s are supported only!',
                $fn, join(',', $fns)));
        }

        $field = $field ?: '*';

        // if as not provided
        if ($as == '') {
            // aggregate('count', 'x') count_x
            // aggregate('count', 'u.x') count_ux
            $as = ($field && $field != '*') ? preg_replace('~[^\w]~', '', $fn .'_'. $field) : $fn;
        }

        return $this->push('select', sprintf('%s(%s) AS %s', $fn, $field, $as));
    }

    /**
     * Run.
     * @return Oppa\Query\Result\ResultInterface
     */
    public function run(): ResultInterface
    {
        return $this->agent->query($this->toString());
    }

    /**
     * Get.
     * @param  string|null $class
     * @return array|object|null
     */
    public function get(string $class = null)
    {
        return $this->agent->get($this->toString(), null, $class);
    }

    /**
     * Get all.
     * @param  string|null $class
     * @return array
     */
    public function getAll(string $class = null): array
    {
        return $this->agent->getAll($this->toString(), null, $class);
    }

    /**
     * Get array.
     * @return ?array
     */
    public function getArray(): ?array
    {
        return $this->get('array');
    }

    /**
     * Get array all.
     * @return array[array]
     */
    public function getArrayAll(): array
    {
        return $this->getAll('array');
    }

    /**
     * Get object.
     * @return ?object
     */
    public function getObject(): ?object
    {
        return $this->get('object');
    }

    /**
     * Get object all.
     * @return array[object]
     */
    public function getObjectAll(): array
    {
        return $this->getAll('object');
    }

    /**
     * Count.
     * @return ?int
     */
    public function count(): ?int
    {
        // no where, count all simply
        $where = $this->query['where'] ?? '';
        if ($where == '') {
            return $this->agent->count($this->table);
        }

        $from = $this->toString();
        if ($from == '') {
            $from = "SELECT 1 FROM {$this->table} WHERE ". join(' ', $where);
        }

        $result = (array) $this->agent->get("SELECT count(*) AS count FROM ({$from}) AS tmp");

        return isset($result['count']) ? intval($result['count']) : null;
    }

    /**
     * To string.
     * @return string
     */
    public function toString(): string
    {
        if (!empty($this->query)) {
            if (isset($this->query['select'])) {
                $string = $this->toQueryString('select');
            } elseif (isset($this->query['insert'])) {
                $string = $this->toQueryString('insert');
            } elseif (isset($this->query['update'])) {
                $string = $this->toQueryString('update');
            } elseif (isset($this->query['delete'])) {
                $string = $this->toQueryString('delete');
            }
        }

        return trim($string ?? '');
    }

    /**
     * To query string.
     * @return ?string
     * @throws Oppa\Query\BuilderException
     */
    public function toQueryString(string $key, ...$options): ?string
    {
        if ($this->table == null) {
            throw new BuilderException(
                "Table is not defined yet! Call 'setTable()' to set target table first.");
        }

        switch ($key) {
            case 'select':
                $string = sprintf('SELECT %s%s FROM %s',
                    join(', ', $this->query['select']),
                    $this->toQueryString('aggregate'),
                    $this->toQueryString('from')
                );

                $string = trim(
                    $string
                    . $this->toQueryString('join')
                    . $this->toQueryString('where')
                    . $this->toQueryString('groupBy')
                    . $this->toQueryString('having')
                    . $this->toQueryString('orderBy')
                    . $this->toQueryString('limit')
                );
                break;
            case 'from':
                $string = $this->query['from'] ?? $this->table;
                break;
            case 'insert':
                if ($this->has('insert')) {
                    $data = $this->query['insert'];

                    $keys = $this->agent->escapeIdentifier(array_keys($data[0]));
                    $values = [];
                    foreach ($data as $dat) {
                        $values[] = '('. $this->agent->escape(array_values($dat)) .')';
                    }

                    $string = "INSERT INTO {$this->table} ({$keys}) VALUES ". join(', ', $values);
                }
                break;
            case 'update':
                if ($this->has('update')) {
                    $data = $this->query['update'];

                    $set = [];
                    foreach ($data as $key => $value) {
                        $set[] = $this->agent->escapeIdentifier($key) .' = '. $this->agent->escape($value);
                    }

                    $string = trim(
                        "UPDATE {$this->table} SET ". join(', ', $set)
                        . $this->toQueryString('where')
                        . $this->toQueryString('orderBy')
                        . $this->toQueryString('limit')
                    );
                }
                break;
            case 'delete':
                if ($this->has('delete')) {
                    $string = trim(
                        "DELETE FROM {$this->table}"
                        . $this->toQueryString('where')
                        . $this->toQueryString('orderBy')
                        . $this->toQueryString('limit')
                    );
                }
                break;
            case 'where':
                if ($this->has('where')) {
                    $ws = []; $wsp = 0;
                    $wheres = $this->query['where'];
                    foreach ($wheres as $i => $where) {
                        [$where, $op] = $where;
                        $n = $wheres[$i + 1] ?? null;
                        $nn = isset($wheres[$i + 2]);
                        $nOp = strtoupper($n[1] ?? '');
                        $ws[] = $where;
                        if ($n) {
                            $ws[] = $op = strtoupper($op);
                        }
                        if ($op != $nOp && $nOp && $nn) {
                            $ws[] = '(';
                            $wsp++;
                        }
                    }

                    $string = preg_replace('~ (OR|AND) \( ([`"])~i', ' \1 (\2', join(' ', $ws)); // :(
                    $string = $string . str_repeat(')', $wsp); // close parentheses
                    $string = " WHERE (\n\t{$string}\n)";
                }
                break;
            case 'groupBy':
                if ($this->has('groupBy')) {
                    $string = ' GROUP BY '. join(', ', $this->query['groupBy']);
                }
                break;
            case 'orderBy':
                if ($this->has('orderBy')) {
                    $string = ' ORDER BY '. join(', ', $this->query['orderBy']);
                }
                break;
            case 'limit':
                if ($this->has('limit')) {
                    $string = isset($this->query['limit'][1])
                        ? ' LIMIT '. $this->query['limit'][0] .' OFFSET '. $this->query['limit'][1]
                        : ' LIMIT '. $this->query['limit'][0];
                }
                break;
            case 'join':
                if ($this->has('join')) {
                    $string = '';
                    foreach ($this->query['join'] as $join) {
                        $string .= ' '. $join;
                    }
                }
                break;
            case 'having':
                if ($this->has('having')) {
                    $string = ' HAVING ('. join(' ', $this->query['having']). ')';
                }
                break;
            case 'aggregate':
                if ($this->has('aggregate')) {
                    $string = ', '. join(', ', $this->query['aggregate']);
                }
                break;
            default:
                throw new BuilderException("Unknown key '{$key}' given");
        }

        return $string ?? null;
    }

    /**
     * Push.
     * @param  string $key
     * @param  any    $value
     * @return self
     */
    private function push(string $key, $value): self
    {
        if (!isset($this->query[$key])) {
            $this->query[$key] = [];
        }

        $this->query[$key] = array_merge($this->query[$key], (array) $value);

        return $this;
    }

    /**
     * Field.
     * @param  string|array|Builder $field
     * @param  bool                 $esc
     * @return string
     */
    private function field($field, bool $esc = true): string
    {
        if ($field instanceof Builder) {
            return '('. $field->toString() .')';
        }

        // eg: ['??', 'id'], ['%n', 'id']
        if (is_array($field)) {
            @ [$field, $fieldName] = $field;
            if ($field == '' || $fieldName == '') {
                throw new BuilderException('Both field and field name required!');
            }
            $field = $this->agent->prepare($field, [$fieldName]);
        }

        if (is_string($field)) {
            $field = trim($field);
            $fieldSearch = substr($field, 0, 2);
            if ($fieldSearch == '%n' || $fieldSearch == '??') {
                $field = '%n'. substr($field, 2);
                $esc = false;
            }

            if ($esc) {
                $field = $this->agent->escapeIdentifier($field);
            }

            return $field;
        }

        throw new BuilderException(sprintf('String, array or Builder type fields are accepted only, %s given',
            gettype($field)));
    }

    /**
     * Prepare.
     * @param  string|array|Builder $field
     * @param  string               $opr
     * @param  array|Builder        $param
     * @return string
     */
    private function prepare($field, string $opr, &$param): string
    {
        $query[] = $this->field($field);
        $query[] = $opr;
        if ($param instanceof Builder) {
            $query[] = '('. $param->toString() .')';
        } else {
            if ($param && !is_array($param) && !is_scalar($param)) {
                throw new BuilderException(sprintf('Scalar or array parameters are accepted only'.
                    ', %s given!', gettype($param)));
            }

            $query[] = $this->agent->prepare('(?)', (array) $param);
        }

        return join(' ', array_filter($query));
    }
}
