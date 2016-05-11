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

namespace Oppa\Database\Query;

use Oppa\Database\Connector\Connection;

/**
 * @package    Oppa
 * @subpackage Oppa\Database\Query
 * @object     Oppa\Database\Query\Builder
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Builder
{
    /**
     * And/or operators.
     * @const string
     */
    const OP_OR = 'OR', OP_AND = 'AND';

    /**
     * Asc/desc operators.
     * @const string
     */
    const OP_ASC = 'ASC', OP_DESC = 'DESC';

    /**
     * Select type for JSON returns.
     * @const string
     */
    const JSON_ARRAY = 'array', JSON_OBJECT = 'object';

    /**
     * Database connection.
     * @var Oppa\Database\Connector\Connection
     */
    private $connection;

    /**
     * Target table for query.
     * @var string
     */
    private $table;

    /**
     * Query stack.
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
     * @param Oppa\Database\Connector\Connection $connection
     * @param string $table
     */
    final public function __construct(Connection $connection = null, string $table = null)
    {
        if ($connection) {
            $this->setConnection($connection);
        }
        if ($table) {
            $this->setTable($table);
        }
    }

    /**
     * String magic.
     * @return string
     */
    final public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Set connection.
     * @param  Oppa\Database\Connector\Connection $connection
     * @return self
     */
    final public function setConnection(Connection $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Get connection.
     * @return Oppa\Database\Connector\Connection
     */
    final public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * Set target table for query.
     * @param  string $table
     * @return self
     */
    final public function setTable(string $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Get target table.
     * @return string
     */
    final public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Reset self vars.
     * @return self
     */
    final public function reset(): self
    {
        $this->query = [];
        $this->queryString = '';

        return $this;
    }

    /**
     * Add select statement.
     * @param  any    $field
     * @param  bool   $reset
     * @param  string $alias (for sub-select)
     * @return self
     */
    final public function select($field = null, bool $reset = true, string $alias = null): self
    {
        $reset && $this->reset();

        // handle other query object
        if ($field instanceof $this) {
            if (empty($alias)) {
                throw new \Exception('Alias is required!');
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

    /**
     * Shortcut for self.select() with no resetting.
     */
    final public function selectMore($field, string $alias = null): self
    {
        return $this->select($field, false, $alias);
    }

    /**
     * Add select statement but returning JSON string.
     * @param  any    $field
     * @param  string $as
     * @param  string $type
     * @param  bool   $reset
     * @return self
     * @throws \Exception
     */
    final public function selectJson($field, string $as, string $type = self::JSON_OBJECT, bool $reset = true): self
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

        throw new \Exception('Given JSON type is not implemented.');
    }

    /**
     * Shortcut for self.selectJson() with no resetting.
     */
    final public function selectMoreJson($field, string $as, string $type = self::JSON_OBJECT): self
    {
        return $this->selectJson($field, $as, $type, false);
    }

    /**
     * Add insert statement.
     * @param  array $data
     * @return self
     */
    final public function insert(array $data): self
    {
        $this->reset();
        // simply check is not assoc to prepare multi-insert
        if (!isset($data[0])) {
            $data = [$data];
        }

        return $this->push('insert', $data);
    }

    /**
     * Add update statement.
     * @param  array $data
     * @return self
     */
    final public function update(array $data): self
    {
        $this->reset();

        return $this->push('update', $data);
    }

    /**
     * Add delete statement.
     * @return self
     */
    final public function delete(): self
    {
        $this->reset();

        return $this->push('delete', true);
    }

    /**
     * Add "JOIN" statement with "ON" keyword.
     * @param  string $table
     * @param  string $on
     * @param  array  $params
     * @return self
     */
    final public function join(string $table, string $on, array $params = null): self
    {
        // Prepare params safely
        if (!empty($params)) {
            $on = $this->connection->getAgent()->prepare($on, $params);
        }

        return $this->push('join', sprintf('JOIN %s ON (%s)', $table, $on));
    }

    /**
     * Add "JOIN" statement with "USING" keyword.
     * @param  string $table
     * @param  string $using
     * @param  array  $params
     * @return self
     */
    final public function joinUsing(string $table, string $using, array $params = null): self
    {
        // Prepare params safely
        if (!empty($params)) {
            $using = $this->connection->getAgent()->prepare($using, $params);
        }

        return $this->push('join', sprintf('JOIN %s USING (%s)', $table, $using));
    }

    /**
     * Add "LEFT JOIN" statement with "ON" keyword.
     * @param  string $table
     * @param  string $on
     * @param  array  $params
     * @return self
     */
    final public function joinLeft(string $table, string $on, array $params = null): self
    {
        // Prepare params safely
        if (!empty($params)) {
            $on = $this->connection->getAgent()->prepare($on, $params);
        }

        return $this->push('join', sprintf('LEFT JOIN %s ON (%s)', $table, $on));
    }

    /**
     * Add "LEFT JOIN" statement with "USING" keyword.
     * @param  string $table
     * @param  string $using
     * @param  array  $params
     * @return self
     */
    final public function joinLeftUsing(string $table, string $using, array $params = null): self
    {
        // Prepare params safely
        if (!empty($params)) {
            $using = $this->connection->getAgent()->prepare($using, $params);
        }

        return $this->push('join', sprintf('LEFT JOIN %s USING (%s)', $table, $using));
    }

    /**
     * Add "WHERE" statement.
     * @param  string $query
     * @param  array  $params
     * @param  string $op
     * @return self
     */
    final public function where(string $query, array $params = null, string $op = self::OP_AND): self
    {
        // prepare if params provided
        if (!empty($params)) {
            $query = $this->connection->getAgent()->prepare($query, $params);
        }

        // add and/or operator
        if (isset($this->query['where']) && !empty($this->query['where'])) {
            $query = sprintf('%s %s', $op, $query);
        }

        return $this->push('where', $query);
    }

    /**
     * Shortcut for self.whereNotEqual().
     */
    final public function whereNot(string $field, $param, string $op = self::OP_AND): self
    {
        return $this->whereNotEqual($field, $param, $op);
    }

    /**
     * Add "WHERE x = .." statement.
     * @param  string $field
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    final public function whereEqual(string $field, $param, string $op = self::OP_AND): self
    {
        return $this->where($field .' = ?', [$param], $op);
    }

    /**
     * Add "WHERE x != .." statement.
     * @param  string $field
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    final public function whereNotEqual(string $field, $param, string $op = self::OP_AND): self
    {
        return $this->where($field .' != ?', [$param], $op);
    }

    /**
     * Add "WHERE" statement for "IS NULL" queries.
     * @param  string $field
     * @param  string $op
     * @return self
     */
    final public function whereNull(string $field, string $op = self::OP_AND): self
    {
        return $this->where($field .' IS NULL', null, $op);
    }

    /**
     * Add "WHERE" statement for "IS NOT NULL" queries.
     * @param  string $field
     * @param  string $op
     * @return self
     */
    final public function whereNotNull(string $field, string $op = self::OP_AND): self
    {
        return $this->where($field .' IS NOT NULL', null, $op);
    }

    /**
     * Add "WHERE" statement for "IN(...)" queries.
     * @param  string $field
     * @param  array  $params
     * @param  string $op
     * @return self
     */
    final public function whereIn(string $field, array $params, string $op = self::OP_AND): self
    {
        return $this->where($field .' IN(?)', [$params], $op);
    }

    /**
     * Add "WHERE" statement for "NOT IN(...)" queries.
     * @param  string $field
     * @param  array  $params
     * @param  string $op
     * @return self
     */
    final public function whereNotIn(string $field, array $params, string $op = self::OP_AND): self
    {
        return $this->where($field .' NOT IN(?)', [$params], $op);
    }

    /**
     * Add "WHERE" statement for "BETWEEN .. AND .." queries.
     * @param  string $field
     * @param  array  $params
     * @param  string $op
     * @return self
     */
    final public function whereBetween(string $field, array $params, string $op = self::OP_AND): self
    {
        return $this->where($field .' BETWEEN ? AND ?', $params, $op);
    }

    /**
     * Add "WHERE" statement for "NOT BETWEEN .. AND .." queries.
     * @param  string $field
     * @param  array  $params
     * @param  string $op
     * @return self
     */
    final public function whereNotBetween(string $field, array $params, string $op = self::OP_AND): self
    {
        return $this->where($field .' NOT BETWEEN ? AND ?', $params, $op);
    }

    /**
     * Add "WHERE" statement for "foo < 123" queries.
     * @param  string $field
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    final public function whereLessThan(string $field, $param, string $op = self::OP_AND): self
    {
        return $this->where($field .' < ?', [$param], $op);
    }

    /**
     * Add "WHERE" statement for "foo <= 123" queries.
     * @param  string $field
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    final public function whereLessThanEqual(string $field, $param, string $op = self::OP_AND): self
    {
        return $this->where($field .' <= ?', [$param], $op);
    }

    /**
     * Add "WHERE" statement for "foo > 123" queries.
     * @param  string $field
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    final public function whereGreaterThan(string $field, $param, string $op = self::OP_AND): self
    {
        return $this->where($field .' > ?', [$param], $op);
    }

    /**
     * Add "WHERE" statement for "foo >= 123" queries.
     * @param  string $field
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    final public function whereGreaterThanEqual(string $field, $param, string $op = self::OP_AND): self
    {
        return $this->where($field .' >= ?', [$param], $op);
    }

    /**
     * Add "WHERE" statement for "LIKE .." queries.
     * @param  string $field
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    final public function whereLike(string $field, $param, string $op = self::OP_AND): self
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
     * Add "WHERE" statement for "LIKE %.." queries.
     * @param  string $field
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    final public function whereLikeStart(string $field, $param, string $op = self::OP_AND): self
    {
        return $this->whereLike($field, $param. '%', $op);
    }

    /**
     * Add "WHERE" statement for "LIKE ..%" queries.
     * @param  string $field
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    final public function whereLikeEnd(string $field, $param, string $op = self::OP_AND): self
    {
        return $this->whereLike($field, '%'. $param, $op);
    }

    /**
     * Add "WHERE" statement for "LIKE %..%" queries.
     * @param  string $field
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    final public function whereLikeBoth(string $field, $param, string $op = self::OP_AND): self
    {
        return $this->whereLike($field, '%'. $param .'%', $op);
    }

    /**
     * Add "MATCH .. AGAINST" queries.
     * @param  string $field
     * @param  string $param
     * @param  string $mode
     * @return string
     */
    final public function whereMatchAgainst(string $field, string $param, string $mode = 'IN BOOLEAN MODE'): self
    {
        return $this->where('MATCH('. $field .') AGAINST(%s '. $mode .')', [$param]);
    }

    /**
     * Add "WHERE" statement for "EXISTS (...)" queries.
     * @param  any    $query
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    final public function whereExists($query, array $params = null, string $op = self::OP_AND): self
    {
        // check query if instance of Builder
        if ($query instanceof Builder) {
            $query = $query->toString();
        }
        // prepare if params provided
        if (!empty($params)) {
            $query = $this->connection->getAgent()->prepare($query, $params);
        }

        return $this->where('EXISTS ('. $query .')', null, $op);
    }

    /**
     * Add "WHERE" statement for "NOT EXISTS (...)" queries.
     * @param  any    $query
     * @param  any    $param
     * @param  string $op
     * @return self
     */
    final public function whereNotExists($query, array $params = null, string $op = self::OP_AND): self
    {
        // check query if instance of Builder
        if ($query instanceof Builder) {
            $query = $query->toString();
        }
        // prepare if params provided
        if (!empty($params)) {
            $query = $this->connection->getAgent()->prepare($query, $params);
        }

        return $this->where('NOT EXISTS ('. $query .')', null, $op);
    }

    /**
     * Add "HAVING" statement.
     * @param  string $query
     * @param  array  $params
     * @param  string $op
     * @return self
     */
    final public function having(string $query, array $params = null, string $op = self::OP_AND): self
    {
        // prepare if params provided
        if (!empty($params)) {
            $query = $this->connection->getAgent()->prepare($query, $params);
        }

        // add and/or operator
        if (isset($this->query['having']) && !empty($this->query['having'])) {
            $query = sprintf('%s %s', $op, $query);
        }

        return $this->push('having', $query);
    }

    /**
     * Add "GROUP BY" statement.
     * @param  string $field
     * @return self
     */
    final public function groupBy(string $field): self
    {
        return $this->push('groupBy', $field);
    }

    /**
     * Add "ORDER BY" statement.
     * @param  string $field
     * @param  string $op
     * @return self
     * @throws \Exception
     */
    final public function orderBy(string $field, string $op = null): self
    {
        // check operator is valid
        if ($op == null) {
            return $this->push('orderBy', $field);
        }

        $op = strtoupper($op);
        if ($op != self::OP_ASC && $op != self::OP_DESC) {
            throw new \Exception('Only available ops: ASC, DESC');
        }

        return $this->push('orderBy', $field .' '. $op);

    }

    /**
     * Add "LIMIT" statement.
     * @param  int      $start
     * @param  int|null $stop
     * @return self
     */
    final public function limit(int $start, int $stop = null): self
    {
        return ($stop === null)
            ? $this->push('limit', $start)
            : $this->push('limit', $start)->push('limit', $stop);
    }

    /**
     * Add a aggregate statement like "COUNT(*)" etc.
     * @param  string      $aggr
     * @param  string      $field
     * @param  string|null $as
     * @return self
     */
    final public function aggregate(string $aggr, string $field = '*', string $as = null): self
    {
        // if alias not provided
        if (empty($as)) {
            $as = ($field && $field != '*')
                // aggregate('count', 'id') count_id
                // aggregate('count', 'u.id') count_uid
                ? preg_replace('~[^\w]~', '', $aggr .'_'. $field) : $aggr;
        }

        return $this->push('select', sprintf('%s(%s) AS %s', $aggr, $field, $as));
    }

    /**
     * Execute builded query.
     * @return any
     */
    final public function execute()
    {
        return $this->connection->getAgent()->query($this->toString());
    }

    /**
     * Shortcut for select one operations.
     * @return any
     */
    final public function get()
    {
        return $this->connection->getAgent()->get($this->toString());
    }

    /**
     * Shortcut for select all operations.
     * @return any
     */
    final public function getAll()
    {
        return $this->connection->getAgent()->getAll($this->toString());
    }

    /**
     * Count.
     * @return int
     */
    final public function count(): int
    {
        $result = $this->connection->getAgent()->get(
            sprintf('SELECT count(*) AS count FROM (%s) AS tmp', $this->toString()));

        return intval($result->count);
    }

    /**
     * Stringify query stack.
     * @return string
     * @throws \Exception
     */
    final public function toString(): string
    {
        // if any query
        if (!empty($this->query)) {
            if (empty($this->table)) {
                throw new \Exception('Table is not defined yet! Call before setTable() to set target table.');
            }
            // reset query
            $this->queryString = '';

            // prapere for "SELECT" statement
            if (isset($this->query['select'])) {
                // add aggregate statements
                $aggregate = isset($this->query['aggregate'])
                    ? ', '. join(', ', $this->query['aggregate'])
                    : '';

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
                    $this->queryString .= !isset($this->query['limit'][1])
                        ? sprintf(' LIMIT %d', $this->query['limit'][0])
                        : sprintf(' LIMIT %d,%d', $this->query['limit'][0], $this->query['limit'][1]);
                }
            }
            // prapere for "INSERT" statement
            elseif (isset($this->query['insert'])) {
                $agent = $this->connection->getAgent();
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
            // prapere for "UPDATE" statement
            elseif (isset($this->query['update'])) {
                $agent = $this->connection->getAgent();
                if ($data = ($this->query['update'] ?? null)) {
                    // prepare "SET" data
                    $set = [];
                    foreach ($data as $key => $value) {
                        $set[] = sprintf('%s = %s',
                            $agent->escapeIdentifier($key), $agent->escape($value));
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
            // prapere for "DELETE" statement
            elseif (isset($this->query['delete'])) {
                $agent = $this->connection->getAgent();

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
     * Add prefix to fields for select, where etc.
     * @param  string $to
     * @param  string $prefix
     * @return void
     */
    final public function addPrefixTo(string $to, string $prefix)
    {
        if (isset($this->query[$to])) {
            $this->query[$to] = array_map(function($field) use($prefix) {
                return sprintf('%s.%s', trim($prefix), trim($field));
            }, $this->query[$to]);
        }
    }

    /**
     * Push a statement and query into query stack.
     * @param  string $key
     * @param  any    $value
     * @return self
     */
    final private function push(string $key, $value): self
    {
        if (!isset($this->query[$key])) {
            $this->query[$key] = [];
        }

        $this->query[$key] = array_merge($this->query[$key], (array) $value);

        return $this;
    }
}
