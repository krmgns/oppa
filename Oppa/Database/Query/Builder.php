<?php
/**
 * Copyright (c) 2015 Kerem Gunes
 *    <http://qeremy.com>
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

namespace Oppa\Database\Query;

use \Oppa\Helper;
use \Oppa\Database\Connector\Connection;
use \Oppa\Exception\Database as Exception;

/**
 * @package    Oppa
 * @subpackage Oppa\Database\Query
 * @object     Oppa\Database\Query\Builder
 * @uses       Oppa\Helper, Oppa\Exception\Database, Oppa\Database\Connector\Connection
 * @version    v1.17
 * @author     Kerem Gunes <qeremy@gmail>
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
    const JSON_ARRAY = 'array',
          JSON_OBJECT = 'object';

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
     * Create a fresh Query Builder object.
     *
     * @param Oppa\Database\Connector\Connection $connection
     * @param string $table
     */
    final public function __construct(Connection $connection = null, $table = null) {
        if ($connection) {
            $this->setConnection($connection);
        }
        if ($table) {
            $this->setTable($table);
        }
    }

    /**
     * Shortcut for debugging.
     *
     * @return string
     */
    final public function __toString() {
        return $this->toString();
    }

    /**
     * Set connection.
     *
     * @param  Oppa\Database\Connector\Connection $connection
     * @return void
     */
    final public function setConnection(Connection $connection) {
        $this->connection = $connection;
    }

    /**
     * Get connection.
     * @return Oppa\Database\Connector\Connection
     */
    final public function getConnection() {
        return $this->connection;
    }

    /**
     * Set target table for query.
     *
     * @param  string $table
     * @return self
     */
    final public function setTable($table) {
        $this->table = $table;

        return $this;
    }

    /**
     * Get target table.
     *
     * @return string
     */
    final public function getTable() {
        return $this->table;
    }

    /**
     * Reset self vars.
     *
     * @return self
     */
    final public function reset() {
        $this->query = [];
        $this->queryString = '';

        return $this;
    }

    /**
     * Add select statement.
     *
     * @param  mixed  $field
     * @param  bool   $reset
     * @param  string $alias (for sub-select)
     * @return self
     */
    final public function select($field = null, $reset = true, $alias = null) {
        $reset && $this->reset();

        // handle other query object
        if ($field instanceof $this) {
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
    final public function selectMore($field, $alias = null) {
        return $this->select($field, false, $alias);
    }

    /**
     * Add select statement but returning JSON string.
     * @param  mixed  $field
     * @param  string $fieldAlias
     * @param  string $type
     * @param  bool   $reset
     * @throws Oppa\Exception\Database\ErrorException
     * @return self
     */
    final public function selectJson($field, $fieldAlias, $type = self::JSON_OBJECT, $reset = true) {
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
            $query = sprintf('CONCAT("{", %s, "}") %s', join(', ', $query), $fieldAlias);
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
            $query = sprintf('CONCAT("[", %s, "]") %s', join(', ', $query), $fieldAlias);
            // yes, ugly but preferable instead of too many concat call or group_concat limit
            // so it's working! tnx: http://www.thomasfrank.se/mysql_to_json.html
            $query = str_replace(',\', "]")', '\'"]")', $query);

            return $this->select([$query], $reset);
        }

        throw new Exception\ErrorException('Given JSON type is not implemented.');
    }

    /**
     * Shortcut for self.selectJson() with no resetting.
     */
    final public function selectMoreJson($field, $fieldAlias, $type = self::JSON_OBJECT) {
        return $this->selectJson($field, $fieldAlias, $type, false);
    }

    /**
     * Add insert statement.
     *
     * @param  array $data
     * @return self
     */
    final public function insert(array $data) {
        $this->reset();
        // simply check is not assoc to prepare multi-insert
        if (!isset($data[0])) {
            $data = [$data];
        }

        return $this->push('insert', $data);
    }

    /**
     * Add update statement.
     *
     * @param  array $data
     * @return self
     */
    final public function update(array $data) {
        $this->reset();

        return $this->push('update', $data);
    }

    /**
     * Add deletet statement.
     *
     * @return self
     */
    final public function delete() {
        $this->reset();

        return $this->push('delete', true);
    }

    /**
     * Add "JOIN" statement with "ON" keyword.
     *
     * @param  string $table To join.
     * @return self
     */
    final public function join($table, $on, array $params = null) {
        // Prepare params safely
        if (!empty($params)) {
            $on = $this->connection->getAgent()->prepare($on, $params);
        }

        return $this->push('join', sprintf('JOIN %s ON %s', $table, $on));
    }

    /**
     * Add "JOIN" statement with "USING" keyword.
     *
     * @param  string $table  To join.
     * @param  string $using
     * @param  array  $params
     * @return self
     */
    final public function joinUsing($table, $using, array $params = null) {
        // Prepare params safely
        if (!empty($params)) {
            $using = $this->connection->getAgent()->prepare($using, $params);
        }

        return $this->push('join', sprintf('JOIN %s USING (%s)', $table, $using));
    }

    /**
     * Add "LEFT JOIN" statement with "ON" keyword.
     *
     * @param  string $table To join.
     * @return self
     */
    final public function joinLeft($table, $on, array $params = null) {
        // Prepare params safely
        if (!empty($params)) {
            $on = $this->connection->getAgent()->prepare($on, $params);
        }

        return $this->push('join', sprintf('LEFT JOIN %s ON %s', $table, $on));
    }

    /**
     * Add "LEFT JOIN" statement with "USING" keyword.
     *
     * @param  string $table  To join.
     * @param  string $using
     * @param  array  $params
     * @return self
     */
    final public function joinLeftUsing($table, $using, array $params = null) {
        // Prepare params safely
        if (!empty($params)) {
            $using = $this->connection->getAgent()->prepare($using, $params);
        }

        return $this->push('join', sprintf('LEFT JOIN %s USING (%s)', $table, $using));
    }

    /**
     * Add "WHERE" statement.
     *
     * @param  string $query
     * @param  array  $params
     * @param  string $op
     * @return self
     */
    final public function where($query, array $params = null, $op = self::OP_AND) {
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
    final public function whereNot($field, $param, $op = self::OP_AND) {
        return $this->whereNotEqual($field, $param, $op);
    }

    /**
     * Add "WHERE x = .." statement.
     *
     * @param  string $field
     * @param  mixed  $param
     * @param  string $op
     * @return self
     */
    final public function whereEqual($field, $param, $op = self::OP_AND) {
        return $this->where($field .' = ?', [$param], $op);
    }

    /**
     * Add "WHERE x != .." statement.
     *
     * @param  string $field
     * @param  mixed  $param
     * @param  string $op
     * @return self
     */
    final public function whereNotEqual($field, $param, $op = self::OP_AND) {
        return $this->where($field .' != ?', [$param], $op);
    }

    /**
     * Add "WHERE" statement for "IS NULL" queries.
     *
     * @param  string $field
     * @param  string $op
     * @return self
     */
    final public function whereNull($field, $op = self::OP_AND) {
        return $this->where($field .' IS NULL', null, $op);
    }

    /**
     * Add "WHERE" statement for "IS NOT NULL" queries.
     *
     * @param  string $field
     * @param  string $op
     * @return self
     */
    final public function whereNotNull($field, $op = self::OP_AND) {
        return $this->where($field .' IS NOT NULL', null, $op);
    }

    /**
     * Add "WHERE" statement for "IN(...)" queries.
     *
     * @param  string $field
     * @param  array  $params
     * @param  string $op
     * @return self
     */
    final public function whereIn($field, array $params, $op = self::OP_AND) {
        return $this->where($field .' IN(?)', [$params], $op);
    }

    /**
     * Add "WHERE" statement for "NOT IN(...)" queries.
     *
     * @param  string $field
     * @param  array  $params
     * @param  string $op
     * @return self
     */
    final public function whereNotIn($field, array $params, $op = self::OP_AND) {
        return $this->where($field .' NOT IN(?)', [$params], $op);
    }

    /**
     * Add "WHERE" statement for "BETWEEN .. AND .." queries.
     *
     * @param  string $field
     * @param  array  $params
     * @param  string $op
     * @return self
     */
    final public function whereBetween($field, array $params, $op = self::OP_AND) {
        return $this->where($field .' BETWEEN ? AND ?', $params, $op);
    }

    /**
     * Add "WHERE" statement for "NOT BETWEEN .. AND .." queries.
     *
     * @param  string $field
     * @param  array  $params
     * @param  string $op
     * @return self
     */
    final public function whereNotBetween($field, array $params, $op = self::OP_AND) {
        return $this->where($field .' NOT BETWEEN ? AND ?', $params, $op);
    }

    /**
     * Add "WHERE" statement for "foo < 123" queries.
     *
     * @param  string $field
     * @param  mixed  $param
     * @param  string $op
     * @return self
     */
    final public function whereLessThan($field, $param, $op = self::OP_AND) {
        return $this->where($field .' < ?', [$param], $op);
    }

    /**
     * Add "WHERE" statement for "foo <= 123" queries.
     *
     * @param  string $field
     * @param  mixed  $param
     * @param  string $op
     * @return self
     */
    final public function whereLessThanEqual($field, $param, $op = self::OP_AND) {
        return $this->where($field .' <= ?', [$param], $op);
    }

    /**
     * Add "WHERE" statement for "foo > 123" queries.
     *
     * @param  string $field
     * @param  mixed  $param
     * @param  string $op
     * @return self
     */
    final public function whereGreaterThan($field, $param, $op = self::OP_AND) {
        return $this->where($field .' > ?', [$param], $op);
    }

    /**
     * Add "WHERE" statement for "foo >= 123" queries.
     *
     * @param  string $field
     * @param  mixed  $param
     * @param  string $op
     * @return self
     */
    final public function whereGreaterThanEqual($field, $param, $op = self::OP_AND) {
        return $this->where($field .' >= ?', [$param], $op);
    }

    /**
     * Shortcut methods.
     */
    final public function whereLT() {
        return call_user_func_array([$this, 'whereLessThan'], func_get_args());
    }
    final public function whereLTE() {
        return call_user_func_array([$this, 'whereLessThanEqual'], func_get_args());
    }
    final public function whereGT() {
        return call_user_func_array([$this, 'whereGreaterThan'], func_get_args());
    }
    final public function whereGTE() {
        return call_user_func_array([$this, 'whereGreaterThanEqual'], func_get_args());
    }

    /**
     * Add "WHERE" statement for "LIKE .." queries.
     *
     * @param  string $field
     * @param  mixed  $param
     * @param  string $op
     * @return self
     */
    final public function whereLike($field, $param, $op = self::OP_AND) {
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
     *
     * @param  string $field
     * @param  mixed  $param
     * @param  string $op
     * @return self
     */
    final public function whereLikeBegin($field, $param, $op = self::OP_AND) {
        return $this->whereLike($field, $param. '%', $op);
    }

    /**
     * Add "WHERE" statement for "LIKE ..%" queries.
     *
     * @param  string $field
     * @param  mixed  $param
     * @param  string $op
     * @return self
     */
    final public function whereLikeEnd($field, $param, $op = self::OP_AND) {
        return $this->whereLike($field, '%'. $param, $op);
    }

    /**
     * Add "WHERE" statement for "LIKE %..%" queries.
     *
     * @param  string $field
     * @param  mixed  $param
     * @param  string $op
     * @return self
     */
    final public function whereLikeBoth($field, $param, $op = self::OP_AND) {
        return $this->whereLike($field, '%'. $param .'%', $op);
    }

    /**
     * Add "MATCH .. AGAINST" queries.
     *
     * @param  string $field
     * @param  string $param
     * @param  string $mode
     * @return string
     */
    final public function whereMatchAgainst($field, $param, $mode = 'IN BOOLEAN MODE') {
        return $this->where('MATCH('. $field .') AGAINST(%s '. $mode .')', [$param]);
    }

    /**
     * Add "WHERE" statement for "EXISTS (...)" queries.
     *
     * @param  mixed  $query
     * @param  mixed  $param
     * @param  string $op
     * @return self
     */
    final public function whereExists($query, array $params = null, $op = self::OP_AND) {
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
     *
     * @param  mixed  $query
     * @param  mixed  $param
     * @param  string $op
     * @return self
     */
    final public function whereNotExists($query, array $params = null, $op = self::OP_AND) {
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
     *
     * @param  string $query
     * @param  array  $params
     * @param  string $op
     * @return self
     */
    final public function having($query, array $params = null, $op = self::OP_AND) {
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
     *
     * @param  string $field
     * @return self
     */
    final public function groupBy($field) {
        return $this->push('groupBy', $field);
    }

    /**
     * Add "ORDER BY" statement.
     *
     * @param  string $field
     * @param  string $op
     * @return self
     */
    final public function orderBy($field, $op = null) {
        // check operator is valid
        if ($op) {
            $op = strtoupper($op);
            if ($op == self::OP_ASC || $op == self::OP_DESC) {
                return $this->push('orderBy', $field .' '. $op);
            }
        }

        return $this->push('orderBy', $field);
    }

    /**
     * Add "LIMIT" statement.
     *
     * @param  integer $start
     * @param  integer $stop
     * @return self
     */
    final public function limit($start, $stop = null) {
        return ($stop === null)
            ? $this->push('limit', $start)
            : $this->push('limit', $start)->push('limit', $stop);
    }

    /**
     * Add a aggregate statement like "COUNT(*)" etc.
     *
     * @param  string      $aggr
     * @param  string      $field
     * @param  string|null $fieldAlias Used for "AS" keyword
     * @return self
     */
    final public function aggregate($aggr, $field = '*', $fieldAlias = null) {
        // if alias not provided
        if (empty($fieldAlias)) {
            $fieldAlias = ($field && $field != '*')
                // aggregate('count', 'id') count_id
                // aggregate('count', 'u.id') count_uid
                ? preg_replace('~[^\w]~', '', $aggr .'_'. $field) : $aggr;
        }

        return $this->push('select', sprintf('%s(%s) %s', $aggr, $field, $fieldAlias));
    }

    /**
     * Execute builded query.
     *
     * @param  callable $callback
     * @return mixed
     */
    final public function execute(callable $callback = null) {
        $result = $this->connection->getAgent()->query($this->toString());
        // Render result if callback provided
        if (is_callable($callback)) {
            $result = $callback($result);
        }

        return $result;
    }

    /**
     * Shortcut for select one operations.
     *
     * @param  callable|null $callback
     * @return mixed
     */
    final public function get(callable $callback = null) {
        $result = $this->connection->getAgent()->get($this->toString());
        if (is_callable($callback)) {
            $result = $callback($result);
        }

        return $result;
    }

    /**
     * Shortcut for select all operations.
     *
     * @param  callable|null $callback
     * @return mixed
     */
    final public function getAll(callable $callback = null) {
        $result = $this->connection->getAgent()->getAll($this->toString());
        if (is_callable($callback)) {
            $result = $callback($result);
        }

        return $result;
    }

    /**
     * Count row sets (using where condition if provided).
     *
     * @return int
     */
    final public function count() {
        $query = sprintf('SELECT count(*) AS count FROM %s', $this->table);
        if (isset($this->query['join'])) {
            $query = sprintf('%s %s', $query, join(' ', $this->query['join']));
        }
        if (isset($this->query['where'])) {
            $query = sprintf('%s WHERE %s', $query, join(' ', $this->query['where']));
        }

        $result = $this->connection->getAgent()->get($query);

        return isset($result->count) ? intval($result->count) : 0;
    }

    /**
     * Stringify query stack.
     *
     * @throws Oppa\Exception\Database\ErrorException
     * @return string
     */
    final public function toString() {
        // if any query
        if (!empty($this->query)) {
            if (empty($this->table)) {
                throw new Exception\ErrorException(
                    'Table is not defined yet! Call before setTable() to set target table.');
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
                    $this->queryString .= sprintf(' WHERE %s', join(' ', $this->query['where']));
                }

                // add group by statement
                if (isset($this->query['groupBy'])) {
                    $this->queryString .= sprintf(' GROUP BY %s', join(', ', $this->query['groupBy']));
                }

                // add having statement
                if (isset($this->query['having'])) {
                    $this->queryString .= sprintf(' HAVING %s', join(' ', $this->query['having']));
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
                if ($data = Helper::getArrayValue('insert', $this->query)) {
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
                if ($data = Helper::getArrayValue('update', $this->query)) {
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
                            $this->queryString .= sprintf(' WHERE %s', join(' ', $this->query['where']));
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
                    $this->queryString .= sprintf(' WHERE %s', join(' ', $this->query['where']));
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
     *
     * @param string $to
     * @param string $prefix
     */
    final public function addPrefixTo($to, $prefix) {
        if (isset($this->query[$to])) {
            $this->query[$to] = array_map(function($field) use($prefix) {
                return sprintf('%s.%s', trim($prefix), trim($field));
            }, $this->query[$to]);
        }
    }

    /**
     * Push a statement and query into query stack.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return self
     */
    final protected function push($key, $value) {
        if (!isset($this->query[$key])) {
            $this->query[$key] = [];
        }
        $this->query[$key] = array_merge($this->query[$key], (array) $value);

        return $this;
    }
}
