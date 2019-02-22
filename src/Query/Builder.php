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

use Oppa\Resource;
use Oppa\Link\Link;
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
     * Set table.
     * @param  string $table
     * @return self
     */
    public function setTable(string $table): self
    {
        $this->table = $table;

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
     * @param  string|array|Builder $field
     * @param  string               $as (for sub-selects)
     * @param  bool                 $reset
     * @return self
     * @throws Oppa\Query\BuilderException
     */
    public function select($field = null, string $as = null, bool $reset = true): self
    {
        $reset && $this->reset();

        // handle other query object
        if ($field instanceof Builder) {
            if (empty($as)) {
                throw new BuilderException('Alias is required!');
            }
            return $this->push('select', sprintf('(%s) AS %s', $field->toString(), $as));
        }

        // handle json select
        if (is_array($field)) {
            return $this->push('select', join('', $field));
        }

        // pass for aggregate method, e.g select().aggregate('count', 'id')
        if (empty($field)) {
            $field = ['1'];
        } else {
            $field = trim($field, ', ');
        }

        return $this->push('select', $field);
    }

    /**
     * Select more.
     * @param  string|array|Builder $field
     * @param  string|null          $as
     * @return self
     */
    public function selectMore($field, string $as = null): self
    {
        return $this->select($field, $as, false);
    }

    /**
     * Select json.
     * @param  string $field
     * @param  string $as
     * @param  string $type
     * @param  bool   $reset
     * @return self
     * @throws Oppa\Query\BuilderException
     */
    public function selectJson(string $field, string $as, string $type = 'object', bool $reset = true): self
    {
        static $agent, $resourceType, $server, $serverVersion, $serverVersionMin, $jsonObject, $jsonArray;
        if ($agent == null) {
            $agent = $this->link->getAgent();
            $resourceType = $agent->getResource()->getType();

            if ($resourceType == Resource::TYPE_MYSQL_LINK) {
                $server = 'MySQL'; $serverVersionMin = '5.7.8';
                $jsonObject = 'json_object'; $jsonArray = 'json_array';
            } elseif ($resourceType == Resource::TYPE_PGSQL_LINK) {
                $server = 'PostgreSQL'; $serverVersionMin = '9.4';
                $jsonObject = 'json_build_object'; $jsonArray = 'json_build_array';
            }

            $serverVersion = $this->link->getDatabase()->getInfo('serverVersion');

            if (version_compare($serverVersion, $serverVersionMin) == -1) {
                throw new BuilderException(sprintf('JSON not supported by %s/v%s, minimum v%s required',
                    $server, $serverVersion, $serverVersionMin));
            }
        }

        $query = [];
        foreach ($this->split('\s*,\s*', $field) as $tmp) {
            $tmp = $this->split('\s*:\s*', $tmp);
            if (!isset($tmp[0], $tmp[1])) {
                throw new BuilderException('Both field name and value should be given!');
            }
            $query[] = $agent->escape($tmp[0]);
            $query[] = $agent->escapeIdentifier($tmp[1]);
        }

        if ($type == 'object') {
            $query = sprintf('%s(%s) AS %s', $jsonObject, join(', ', $query), $as);
        } elseif ($type == 'array') {
            $query = sprintf('%s(%s) AS %s', $jsonArray, join(', ', $query), $as);
        } else {
            throw new BuilderException("Given JSON type '{$type}' is not implemented!");
        }

        return $this->select($query, $as, $reset);
    }

    /**
     * Select more json.
     * @param  string $field
     * @param  string $as
     * @param  string $type
     * @return self
     */
    public function selectMoreJson(string $field, string $as, string $type = 'object'): self
    {
        return $this->selectJson($field, $as, $type, false);
    }

    /**
     * From
     * @param  string|Builder $field
     * @param  string|null    $as
     * @return [type]
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
        // prepare params safely
        if (!empty($params)) {
            $on = $this->link->getAgent()->prepare($on, $params);
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
        // prepare params safely
        if (!empty($params)) {
            $using = $this->link->getAgent()->prepare($using, $params);
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
        // prepare params safely
        if (!empty($params)) {
            $on = $this->link->getAgent()->prepare($on, $params);
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
        // prepare params safely
        if (!empty($params)) {
            $using = $this->link->getAgent()->prepare($using, $params);
        }

        return $this->push('join', sprintf('LEFT JOIN %s USING (%s)', $table, $using));
    }

    /**
     * Where.
     * @param  string $query
     * @param  any    $queryParams
     * @param  string $op
     * @return self
     */
    public function where(string $query, $queryParams = null, string $op = self::OP_AND): self
    {
        // sub-where
        if ($queryParams instanceof Builder) {
            // $opr argument is empty, should be exists in query (eg: id = )
            $query = $this->prepare($query, '', $queryParams);

            return $this->push('where', $query);
        }

        if ($queryParams !== null) {
            if (!is_array($queryParams) && !is_scalar($queryParams)) {
                throw new BuilderException(sprintf('Only array or scalar parameters are accepted'.
                    ', %s given!', gettype($queryParams)));
            }
        }

        $query = $this->link->getAgent()->prepare($query, (array) $queryParams);

        // add and/or operator
        if (!empty($this->query['where'])) {
            $query = sprintf('%s %s', $op, $query);
        }

        return $this->push('where', $query);
    }

    /**
     * Where equal.
     * @param  string|Builder $field
     * @param  any            $param
     * @param  string         $op
     * @return self
     */
    public function whereEqual($field, $param, string $op = self::OP_AND): self
    {
        return $this->where($this->prepare($field, '=', $param), null, $op);
    }

    /**
     * Where not equal.
     * @param  string|Builder $field
     * @param  any            $param
     * @param  string         $op
     * @return self
     */
    public function whereNotEqual($field, $param, string $op = self::OP_AND): self
    {
        return $this->where($this->prepare($field, '!=', $param), null, $op);
    }

    /**
     * Where null.
     * @param  string|Builder $field
     * @param  string         $op
     * @return self
     */
    public function whereNull($field, string $op = self::OP_AND): self
    {
        return $this->where($this->field($field) .' IS NULL', null, $op);
    }

    /**
     * Where not null.
     * @param  string|Builder $field
     * @param  string         $op
     * @return self
     */
    public function whereNotNull($field, string $op = self::OP_AND): self
    {
        return $this->where($this->field($field) .' IS NOT NULL', null, $op);
    }

    /**
     * Where in.
     * @param  string        $field
     * @param  array|Builder $param
     * @param  string        $op
     * @return self
     */
    public function whereIn(string $field, $param, string $op = self::OP_AND): self
    {
        if (is_array($param)) {
            $param = [$param];
        }
        return $this->where($this->prepare($field, 'IN', $param), null, $op);
    }

    /**
     * Where not in.
     * @param  string        $field
     * @param  array|Builder $param
     * @param  string        $op
     * @return self
     */
    public function whereNotIn(string $field, $param, string $op = self::OP_AND): self
    {
        if (is_array($param)) {
            $param = [$param];
        }
        return $this->where($this->prepare($field, 'NOT IN', $param), null, $op);
    }

    /**
     * Where between.
     * @param  string|Builder $field
     * @param  array          $params
     * @param  string         $op
     * @return self
     */
    public function whereBetween($field, array $params, string $op = self::OP_AND): self
    {
        return $this->where($this->field($field) .' BETWEEN (? AND ?)', $params, $op);
    }

    /**
     * Where not between.
     * @param  string|Builder $field
     * @param  array          $params
     * @param  string         $op
     * @return self
     */
    public function whereNotBetween($field, array $params, string $op = self::OP_AND): self
    {
        return $this->where($this->field($field) .' NOT BETWEEN (? AND ?)', $params, $op);
    }

    /**
     * Where less than.
     * @param  string|Builder $field
     * @param  any            $param
     * @param  string         $op
     * @return self
     */
    public function whereLessThan($field, $param, string $op = self::OP_AND): self
    {
        return $this->where($this->prepare($field, '<', $param), null, $op);
    }

    /**
     * Where less than equal.
     * @param  string|Builder $field
     * @param  any            $param
     * @param  string         $op
     * @return self
     */
    public function whereLessThanEqual($field, $param, string $op = self::OP_AND): self
    {
        return $this->where($this->prepare($field, '<=', $param), null, $op);
    }

    /**
     * Where greater than.
     * @param  string|Builder $field
     * @param  any            $param
     * @param  string         $op
     * @return self
     */
    public function whereGreaterThan($field, $param, string $op = self::OP_AND): self
    {
        return $this->where($this->prepare($field, '>', $param), null, $op);
    }

    /**
     * Where greater than equal.
     * @param  string|Builder $field
     * @param  any            $param
     * @param  string         $op
     * @return self
     */
    public function whereGreaterThanEqual($field, $param, string $op = self::OP_AND): self
    {
        return $this->where($this->prepare($field, '>=', $param), null, $op);
    }

    /**
     * Where like.
     * @param  string        $field
     * @param  string        $format
     * @param  string|scalar $param
     * @param  string $op
     * @return self
     */
    public function whereLike(string $field, string $format, $param, string $op = self::OP_AND): self
    {
        if (!is_scalar($param)) {
            throw new BuilderException(sprintf('Only string or scalar parameters are accepted'.
                ', %s given!', gettype($param)));
        }

        // 'foo%'  Anything starts with "foo"
        // '%foo'  Anything ends with "foo"
        // '%foo%' Anything have "foo" in any position
        // 'f_o%'  Anything have "o" in the second position
        // 'f_%_%' Anything starts with "f" and are at least 3 characters in length
        // 'f%o'   Anything starts with "f" and ends with "o"

        @ [$start, $end] = explode('-', $format);

        return $this->where($field. " LIKE '". sprintf('%s%%sl%s', $end, $start) ."'", $param, $op);
    }

    /**
     * Where like start.
     * @param  string $field
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    public function whereLikeStart(string $field, $param, string $op = self::OP_AND): self
    {
        return $this->whereLike($field, '%-', $param, $op);
    }

    /**
     * Where like end.
     * @param  string $field
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    public function whereLikeEnd(string $field, $param, string $op = self::OP_AND): self
    {
        return $this->whereLike($field, '-%', $param, $op);
    }

    /**
     * Where like both.
     * @param  string $field
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    public function whereLikeBoth(string $field, $param, string $op = self::OP_AND): self
    {
        return $this->whereLike($field, '%-%', $param, $op);
    }

    /**
     * Where match against.
     * @param  string $field
     * @param  string $param
     * @param  string $mode
     * @return string
     */
    public function whereMatchAgainst(string $field, string $param, string $mode = ''): self
    {
        return $this->where('match('. $field .') against(%s '. ($mode ?: 'IN BOOLEAN MODE') .')', [$param]);
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

        if (!empty($params)) {
            $query = $this->link->getAgent()->prepare($query, $params);
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

        if (!empty($params)) {
            $query = $this->link->getAgent()->prepare($query, $params);
        }

        return $this->where('NOT EXISTS ('. $query .')', null, $op);
    }

    /**
     * Where id.
     * @param  string     $idField
     * @param  int|string $idParam
     * @param  string     $op
     * @return self
     */
    public function whereId(string $idField, $idParam, string $op = self::OP_AND): self
    {
        return $this->where($this->prepare($idField, '=', $idParam), null, $op);
    }

    /**
     * Where ids.
     * @param  string            $idField
     * @param  array[int|string] $idParams
     * @param  string            $op
     * @return self
     */
    public function whereIds(string $idField, array $idParams, string $op = self::OP_AND): self
    {
        return $this->where($this->prepare($idField, 'IN', [$idParams]), null, $op);
    }

    /**
     * Id (shortcut for where id = ?).
     * @param  int|string $id
     * @param  string     $op
     * @return self
     */
    public function id($id, string $op = self::OP_AND): self
    {
        return $this->where('id = ?', $id, $op);
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
        // prepare if params provided
        if (!empty($params)) {
            $query = $this->link->getAgent()->prepare($query, $params);
        }

        // add and/or operator
        if (!empty($this->query['having'])) {
            $query = sprintf('%s %s', $op, $query);
        }

        return $this->push('having', $query);
    }

    /**
     * Group by.
     * @param  string $field
     * @return self
     */
    public function groupBy(string $field): self
    {
        return $this->push('groupBy', $this->field($field));
    }

    /**
     * Order by.
     * @param  string $field
     * @param  string $op
     * @return self
     * @throws Oppa\Query\BuilderException
     */
    public function orderBy(string $field, string $op = null): self
    {
        // check operator is valid
        if ($op == null) {
            return $this->push('orderBy', $this->field($field));
        }

        $op = strtoupper($op);
        if ($op != self::OP_ASC && $op != self::OP_DESC) {
            throw new BuilderException('Only available ops: ASC, DESC');
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
            throw new BuilderException('Limit not set yet, call Builder::limit() first');
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
        return $this->link->getAgent()->query($this->toString());
    }

    /**
     * Get.
     * @param  string|null $class
     * @return array|object|null
     */
    public function get(string $class = null)
    {
        return $this->link->getAgent()->get($this->toString(), null, $class);
    }

    /**
     * Get all.
     * @param  string|null $class
     * @return array
     */
    public function getAll(string $class = null): array
    {
        return $this->link->getAgent()->getAll($this->toString(), null, $class);
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
     * @return array
     */
    public function getArrayAll(): array
    {
        return $this->getAll('array');
    }

    /**
     * Get object.
     * @return ?object
     */
    public function getObject()
    {
        return $this->get('object');
    }

    /**
     * Get object all.
     * @return array
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
        $agent = $this->link->getAgent();

        // no where, count all simply
        $where = $this->query['where'] ?? '';
        if ($where == '') {
            return $agent->count($this->table);
        }

        $from = $this->toString();
        if ($from == '') {
            $from = "SELECT 1 FROM {$this->table} WHERE ". join(' ', $where);
        }

        $result = (array) $agent->get("SELECT count(*) AS count FROM ({$from}) AS tmp");

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
                    $agent = $this->link->getAgent();

                    $keys = join(', ', array_keys($data[0]));
                    $values = [];
                    foreach ($data as $dat) {
                        $values[] = '('. $agent->escape(array_values($dat)) .')';
                    }

                    $string = "INSERT INTO {$this->table} ({$keys}) VALUES ". join(', ', $values);
                }
                break;
            case 'update':
                if ($this->has('update')) {
                    $data = $this->query['update'];
                    $agent = $this->link->getAgent();

                    $set = [];
                    foreach ($data as $key => $value) {
                        $set[] = sprintf('%s = %s', $key, $agent->escape($value));
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
                    $string = ' WHERE ('. join(' ', $this->query['where']) .')';
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
     * @param  string|Builder $field
     * @return string
     */
    private function field($field): string
    {
        if ($field instanceof Builder) {
            $field = '('. $field->toString() .')';
        }

        return trim($field);
    }

    /**
     * Prepare.
     * @param  string|Builder $field
     * @param  string         $opr
     * @param  array|Builder  $param
     * @return string
     */
    private function prepare($field, string $opr, $param): string
    {
        $query[] = $this->field($field);
        $query[] = $opr;
        if ($param instanceof Builder) {
            $query[] = '('. $param->toString() .')';
        } else {
            if ($param && !is_scalar($param) && !is_array($param)) {
                throw new BuilderException(sprintf('Only scalar or array parameters are accepted'.
                    ', %s given!', gettype($param)));
            }
            $query[] = $this->link->getAgent()->prepare('(?)', (array) $param);
        }

        return join(' ', array_filter($query));
    }

    /**
     * Split.
     * @param  string $pattern
     * @param  string $input
     * @return array
     */
    private function split(string $pattern, string $input): array
    {
        return (array) preg_split('~'. trim($pattern, '~') .'~', $input, -1, PREG_SPLIT_NO_EMPTY);
    }
}
