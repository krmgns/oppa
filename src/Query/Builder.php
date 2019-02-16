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

use Oppa\Link\Link;
use Oppa\Query\Result\ResultInterface;
use Oppa\Exception\InvalidValueException;

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
     * Select type for JSON returns.
     * @const string
     */
    public const JSON_ARRAY  = 'array',
                 JSON_OBJECT = 'object';

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
     * Query string.
     * @var string
     */
    private $queryString = '';

    /**
     * Constructor.
     * @param Oppa\Link\Link $link
     * @param string $table
     */
    public function __construct(Link $link = null, string $table = null)
    {
        if ($link) {
            $this->setLink($link);
        }
        if ($table) {
            $this->setTable($table);
        }
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
        $this->queryString = '';

        return $this;
    }

    /**
     * Select.
     * @param  any    $field
     * @param  bool   $reset
     * @param  string $alias (for sub-select)
     * @return self
     * @throws Oppa\Exception\InvalidValueException
     */
    public function select($field = null, bool $reset = true, string $alias = null): self
    {
        $reset && $this->reset();

        // handle other query object
        if ($field instanceof $this) {
            if (empty($alias)) {
                throw new InvalidValueException('Alias is required!');
            }
            return $this->push('select', sprintf('(%s) AS %s', $field->toString(), $alias));
        }

        // handle json select
        if (is_array($field)) {
            return $this->push('select', join('', $field));
        }

        // pass for aggregate method, e.g select().aggregate('count', 'id')
        if (empty($field)) {
            $field = ['1'];
        }

        return $this->push('select', trim($field, ', '));
    }

    /** Shortcut for self.select() with no reset. */
    public function selectMore($field, string $alias = null): self
    {
        return $this->select($field, false, $alias);
    }

    /**
     * Select json.
     * @param  any    $field
     * @param  string $as
     * @param  string $type
     * @param  bool   $reset
     * @return self
     * @throws Oppa\Exception\InvalidValueException
     */
    public function selectJson($field, string $as, string $type = self::JSON_OBJECT, bool $reset = true): self
    {
        if (is_string($field)) {
            // field1, field2 ..
            $field = array_map('trim', explode(',', $field));
        }

        $query = [];
        // json object
        if ($type == self::JSON_OBJECT) {
            foreach ($field as $field) {
                $key = $field;
                // handle "a.field foo" or "a.field as foo"
                $tmp = preg_split('~[\s\.](?:as|)~i', $key, -1, PREG_SPLIT_NO_EMPTY);
                if (isset($tmp[2])) {
                    $key = $tmp[2];
                    $field = substr($field, 0, strpos($field, ' '));
                } elseif (isset($tmp[1])) {
                    $key = $tmp[1];
                }
                // generate sub-concat escaping quots
                $query[] = sprintf("'\"%s\":\"', REPLACE(%s, '\"', '\\\\\"'), '\",'", $key, $field);
            }

            // generate concat
            $query = sprintf('CONCAT("{", %s, "}") AS %s', join(', ', $query), $as);
            // yes, ugly but preferable instead of too many concat call or group_concat limit
            // so it's working! tnx: http://www.thomasfrank.se/mysql_to_json.html
            $query = str_replace(',\', "}")', '\'"}")', $query);

            return $this->select([$query], $reset);
        }

        // json array
        if ($type == self::JSON_ARRAY) {
            foreach ($field as $field) {
                $key = $field;
                // handle "a.field foo" or "a.field as foo"
                if ($pos = strpos($field, ' ')) {
                    $key = substr($field, 0, $pos);
                }
                // generate sub-concat escaping quots
                $query[] = sprintf("'\"', REPLACE(%s, '\"', '\\\\\"'), '\",'", $key, $field);
            }

            // generate concat
            $query = sprintf('CONCAT("[", %s, "]") AS %s', join(', ', $query), $as);
            // yes, ugly but preferable instead of too many concat call or group_concat limit
            // so it's working! tnx: http://www.thomasfrank.se/mysql_to_json.html
            $query = str_replace(',\', "]")', '\'"]")', $query);

            return $this->select([$query], $reset);
        }

        throw new InvalidValueException('Given JSON type is not implemented.');
    }

    /** Shortcut for self.selectJson() with no reset. */
    public function selectMoreJson($field, string $as, string $type = self::JSON_OBJECT): self
    {
        return $this->selectJson($field, $as, $type, false);
    }

    /** Shortcut for self.selectJson() with JSON_OBJECT type. */
    public function selectJsonObject($field, string $as, bool $reset = true): self
    {
        return $this->selectJson($field, $as, self::JSON_OBJECT, $reset);
    }

    /** Shortcut for self.selectJson() with JSON_OBJECT type with no reset. */
    public function selectMoreJsonObject($field, string $as): self
    {
        return $this->selectMoreJson($field, $as, self::JSON_OBJECT);
    }

    /** Shortcut for self.selectJson() with JSON_ARRAY type. */
    public function selectJsonArray($field, string $as, bool $reset = true): self
    {
        return $this->selectJson($field, $as, self::JSON_ARRAY, $reset);
    }

    /** Shortcut for self.selectJson() with JSON_ARRAY type with no reset. */
    public function selectMoreJsonArray($field, string $as): self
    {
        return $this->selectMoreJson($field, $as, self::JSON_ARRAY);
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
                throw new InvalidValueException(sprintf('Only array or scalar parameters are accepted'.
                    ', %s given!', gettype($queryParams)));
            }

            $query = $this->link->getAgent()->prepare($query, (array) $queryParams);
        }

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
        return $this->where($this->field($field) .' BETWEEN ? AND ?', $params, $op);
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
        return $this->where($this->field($field) .' NOT BETWEEN ? AND ?', $params, $op);
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
     * @param  string $field
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    public function whereLike(string $field, $param, string $op = self::OP_AND): self
    {
        $fChar = strval($param[0]);
        $lChar = substr(strval($param), -1);
        // both appended
        if ($fChar == '%' && $lChar == '%') {
            $param = $fChar . addcslashes(substr($param, 1, -1), '%_') . $lChar;
        }
        // left appended
        elseif ($fChar == '%') {
            $param = $fChar . addcslashes(substr($param, 1), '%_');
        }
        // right appended
        elseif ($lChar == '%') {
            $param = addcslashes(substr($param, 0, -1), '%_') . $lChar;
        } // else no willcards

        return $this->where($field .' LIKE ?', [$param], $op);
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
        return $this->whereLike($field, $param. '%', $op);
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
        return $this->whereLike($field, '%'. $param, $op);
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
        return $this->whereLike($field, '%'. $param .'%', $op);
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
        return $this->where('MATCH('. $field .') AGAINST(%s '. ($mode ?: 'IN BOOLEAN MODE') .')', [$param]);
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
        return $this->push('groupBy', $field);
    }

    /**
     * Order by.
     * @param  string $field
     * @param  string $op
     * @return self
     * @throws Oppa\Exception\InvalidValueException
     */
    public function orderBy(string $field, string $op = null): self
    {
        // check operator is valid
        if ($op == null) {
            return $this->push('orderBy', $field);
        }

        $op = strtoupper($op);
        if ($op != self::OP_ASC && $op != self::OP_DESC) {
            throw new InvalidValueException('Only available ops: ASC, DESC');
        }

        return $this->push('orderBy', $field .' '. $op);

    }

    /**
     * Limit.
     * @param  int      $start
     * @param  int|null $stop
     * @return self
     */
    public function limit(int $start, int $stop = null): self
    {
        return ($stop === null)
            ? $this->push('limit', $start)
            : $this->push('limit', $start)->push('limit', $stop);
    }

    /**
     * Aggregate.
     * @param  string      $func
     * @param  string      $field
     * @param  string|null $as
     * @return self
     */
    public function aggregate(string $func, string $field = '*', string $as = null): self
    {
        // if alias not provided
        if (empty($as)) {
            $as = ($field && $field != '*')
                // aggregate('count', 'id') count_id
                // aggregate('count', 'u.id') count_uid
                ? preg_replace('~[^\w]~', '', $func .'_'. $field) : $func;
        }

        return $this->push('select', sprintf('%s(%s) AS %s', $func, $field, $as));
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
     * @return any
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
     * @throws \LogicException
     */
    public function toString(): string
    {
        // if any query
        if (!empty($this->query)) {
            if (empty($this->table)) {
                throw new \LogicException(
                    "Table is not defined yet! Call 'setTable()' to set target table first.");
            }

            $this->queryString = '';

            // prepare for "SELECT" statement
            if (isset($this->query['select'])) {
                // add aggregate statements
                $aggregate = isset($this->query['aggregate'])
                    ? ', '. join(', ', $this->query['aggregate']) : '';

                // add select fields
                $this->queryString .= sprintf('SELECT %s%s FROM %s',
                    join(', ', $this->query['select']), $aggregate, $this->table);

                // add join statements
                if (isset($this->query['join'])) {
                    foreach ($this->query['join'] as $value) {
                        $this->queryString .= sprintf(' %s', $value);
                    }
                }

                // add where statement
                if (isset($this->query['where'])) {
                    $this->queryString .= sprintf(' WHERE (%s)', join(' ', $this->query['where']));
                }

                // add group by statement
                if (isset($this->query['groupBy'])) {
                    $this->queryString .= sprintf(' GROUP BY %s', join(', ', $this->query['groupBy']));
                }

                // add having statement
                if (isset($this->query['having'])) {
                    $this->queryString .= sprintf(' HAVING (%s)', join(' ', $this->query['having']));
                }

                // add order by statement
                if (isset($this->query['orderBy'])) {
                    $this->queryString .= sprintf(' ORDER BY %s', join(', ', $this->query['orderBy']));
                }

                // add limit statement
                if (isset($this->query['limit'])) {
                    $this->queryString .= isset($this->query['limit'][1])
                        ? sprintf(' LIMIT %d OFFSET %d', $this->query['limit'][1], $this->query['limit'][0])
                        : sprintf(' LIMIT %d', $this->query['limit'][0]);
                }
            }
            // prepare for "INSERT" statement
            elseif (isset($this->query['insert'])) {
                $agent = $this->link->getAgent();
                if ($data = ($this->query['insert'] ?? null)) {
                    $keys = $agent->escapeIdentifier(array_keys(current($data)));
                    $values = [];
                    foreach ($data as $d) {
                        $values[] = '('. $agent->escape(array_values($d)) .')';
                    }

                    $this->queryString = sprintf(
                        "INSERT INTO {$this->table} ({$keys}) VALUES %s", join(', ', $values));
                }
            }
            // prepare for "UPDATE" statement
            elseif (isset($this->query['update'])) {
                $agent = $this->link->getAgent();
                if ($data = ($this->query['update'] ?? null)) {
                    // prepare "SET" data
                    $set = [];
                    foreach ($data as $key => $value) {
                        $set[] = sprintf('%s = %s', $agent->escapeIdentifier($key), $agent->escape($value));
                    }
                    // check again
                    if (!empty($set)) {
                        $this->queryString = sprintf(
                            "UPDATE {$this->table} SET %s", join(', ', $set));

                        // add criterias
                        if (isset($this->query['where'])) {
                            $this->queryString .= sprintf(' WHERE (%s)', join(' ', $this->query['where']));
                        }
                        if (isset($this->query['orderBy'])) {
                            $this->queryString .= sprintf(' ORDER BY %s', join(', ', $this->query['orderBy']));
                        }
                        if (isset($this->query['limit'][0])) {
                            $this->queryString .= sprintf(' LIMIT %d', $this->query['limit'][0]);
                        }
                    }
                }
            }
            // prepare for "DELETE" statement
            elseif (isset($this->query['delete'])) {
                $this->queryString = "DELETE FROM {$this->table}";

                // add criterias
                if (isset($this->query['where'])) {
                    $this->queryString .= sprintf(' WHERE (%s)', join(' ', $this->query['where']));
                }
                if (isset($this->query['orderBy'])) {
                    $this->queryString .= sprintf(' ORDER BY %s', join(', ', $this->query['orderBy']));
                }
                if (isset($this->query['limit'][0])) {
                    $this->queryString .= sprintf(' LIMIT %d', $this->query['limit'][0]);
                }
            }
        }

        return trim($this->queryString);
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
            if ($param && !is_array($param) && !is_scalar($param)) {
                throw new InvalidValueException(sprintf('Only array or scalar parameters are accepted'.
                    ', %s given!', gettype($param)));
            }
            $query[] = $this->link->getAgent()->prepare('(?)', (array) $param);
        }

        return join(' ', array_filter($query));
    }
}
