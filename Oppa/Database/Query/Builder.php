<?php namespace Oppa\Database\Query;

use \Oppa\Helper;
use \Oppa\Database\Connector\Connection;

final class Builder
{
    // Operators
    const OP_OR   = 'OR',
          OP_AND  = 'AND',
          OP_ASC  = 'ASC',
          OP_DESC = 'DESC';


    private $query = [];
    private $queryString = '';

    private $table;

    private $connection;

    final public function __construct(Connection $connection = null) {
        if ($connection) {
            $this->setConnection($connection);
        }
    }

    final public function __toString() {
        return $this->toString();
    }

    final public function setConnection(Connection $connection) {
        $this->connection = $connection;
    }

    final public function getConnection() {
        return $this->connection;
    }

    final public function setTable($table) {
        $this->table = $table;
    }
    final public function getTable() {
        return $this->table;
    }

    final public function reset() {
        $this->query = [];
        $this->queryString = '';
        return $this;
    }

    final public function select($field = null) {
        $this->reset();
        // pass for aggregate method, e.g select().aggregate('count', 'id')
        if (empty($field)) {
            $field = 1;
        }
        return $this->push('select', $field);
    }

    final public function insert(array $data) {
        $this->reset();
        // simply check is not assoc to prepare multi-insert
        if (!isset($data[0])) {
            $data = [$data];
        }
        return $this->push('insert', $data, false);
    }

    final public function update(array $data) {
        $this->reset();
        return $this->push('update', $data, false);
    }

    final public function delete() {
        $this->reset();
        return $this->push('delete', true, false);
    }

    final public function joinLeft($table, $on, array $params = null) {
        // Prepare params safely
        if (!empty($params)) {
            $on = $this->connection->getAgent()->prepare($on, $params);
        }

        return $this->push('join', sprintf('%s ON %s', $table, $on));
    }

    final public function joinLeftUsing($table, $using, array $params = null) {
        // Prepare params safely
        if (!empty($params)) {
            $using = $this->connection->getAgent()->prepare($using, $params);
        }

        return $this->push('join', sprintf('%s USING (%s)', $table, $using));
    }

    final public function where($query, array $params = null, $op = self::OP_AND) {
        if (!empty($params)) {
            $query = $this->connection->getAgent()->prepare($query, $params);
        }
        if (isset($this->query['where']) && !empty($this->query['where'])) {
            $query = sprintf('%s %s', $op, $query);
        }

        return $this->push('where', $query);
    }

    final public function whereLike($query, array $params = null, $op = self::OP_AND) {
        if (!empty($params)) {
            foreach ($params as &$param) {
                $charFirst = strval($param[0]);
                $charLast  = substr($param, -1);
                // both appended
                if ($charFirst == '%' && $charLast == '%') {
                    $param = $charFirst . addcslashes(substr($param, 1, -1), '%_') . $charLast;
                }
                // left appended
                elseif ($charFirst == '%') {
                    $param = $charFirst . addcslashes(substr($param, 1), '%_');
                }
                // right appended
                elseif ($charLast == '%') {
                    $param = addcslashes(substr($param, 0, -1), '%_') . $charLast;
                }
            }
        }

        return $this->where($query, $params, $op);
    }

    final public function whereNull($field) {
        return $this->push('where', sprintf('%s IS NULL', $field));
    }

    final public function whereNotNull($field) {
        return $this->push('where', sprintf('%s IS NOT NULL', $field));
    }

    final public function having($query, array $params = null) {
        if (!empty($params)) {
            $query = $this->connection->getAgent()->prepare($query, $params);
        }

        return $this->push('having', $query);
    }

    final public function groupBy($field) {
        return $this->push('groupBy', $field);
    }

    final public function orderBy($field, $op = null) {
        if ($op == self::OP_ASC || $op == self::OP_DESC) {
            return $this->push('orderBy', $field .' '. $op);
        }
        return $this->push('orderBy', $field);
    }

    final public function limit($start, $stop = null) {
        return ($stop === null)
            ? $this->push('limit', $start)
            : $this->push('limit', $start)->push('limit', $stop);
    }

    final public function aggregate($aggr, $field = '*', $fieldAlias = null) {
        if (empty($fieldAlias)) {
            $fieldAlias = ($field && $field != '*')
                // aggregate('count', 'id') count_id
                // aggregate('count', 'u.id') count_uid
                ? preg_replace('~[^\w]~', '', $aggr .'_'. $field) : $aggr;
        }
        return $this->push('aggregate', sprintf('%s(%s) %s',
            $aggr, $field, $fieldAlias
        ));
    }

    final public function execute(callable $callback = null) {
        $result = $this->connection->getAgent()->query($this->toString());
        // Render result if callback provided
        if (is_callable($callback)) {
            $result = $callback($result);
        }

        return $result;
    }

    final public function get(callable $callback = null) {
        $result = $this->connection->getAgent()->get($this->toString());
        if (is_callable($callback)) {
            $result = $callback($result);
        }

        return $result;
    }

    final public function getAll(callable $callback = null) {
        $result = $this->connection->getAgent()->getAll($this->toString());
        if (is_callable($callback)) {
            $result = $callback($result);
        }

        return $result;
    }

    final public function toString() {
        // Set once query string
        if (!empty($this->query) && empty($this->queryString)) {
            // Add select statement
            if (isset($this->query['select'])) {
                // Add aggregate statements
                $aggregate = isset($this->query['aggregate'])
                    ? ', '. join(', ', $this->query['aggregate'])
                    : '';
                $this->queryString .= sprintf('SELECT %s%s FROM %s',
                    join(', ', $this->query['select']), $aggregate, $this->table);

                // Add left join statement
                if (isset($this->query['join'])) {
                    $this->queryString .= sprintf(' LEFT JOIN %s', join(' ', $this->query['join']));
                }

                // Add where statement
                if (isset($this->query['where'])) {
                    $this->queryString .= sprintf(' WHERE %s', join(' ', $this->query['where']));
                }

                // Add group by statement
                if (isset($this->query['groupBy'])) {
                    $this->queryString .= sprintf(' GROUP BY %s', join(', ', $this->query['groupBy']));
                }

                // Add having statement
                if (isset($this->query['having'])) {
                    // Use only first element of having for now..
                    $this->queryString .= sprintf(' HAVING %s', $this->query['having'][0]);
                }

                // Add order by statement
                if (isset($this->query['orderBy'])) {
                    $this->queryString .= sprintf(' ORDER BY %s', join(', ', $this->query['orderBy']));
                }

                // Add limit statement
                if (isset($this->query['limit'])) {
                    $this->queryString .= !isset($this->query['limit'][1])
                        ? sprintf(' LIMIT %d', $this->query['limit'][0])
                        : sprintf(' LIMIT %d,%d', $this->query['limit'][0], $this->query['limit'][1]);
                }
            } elseif (isset($this->query['insert'])) {
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
            } elseif (isset($this->query['update'])) {
                $agent = $this->connection->getAgent();
                if ($data = Helper::getArrayValue('update', $this->query)) {
                    $set = [];
                    foreach ($data as $key => $value) {
                        $set[] = sprintf('%s = %s',
                            $agent->escapeIdentifier($key), $agent->escape($value));
                    }

                    $this->queryString = sprintf(
                        "UPDATE {$this->table} SET %s", join(', ', $set));

                    if (isset($this->query['where'])) {
                        $this->queryString .= sprintf(' WHERE %s', join(' ', $this->query['where']));
                    }
                    if (isset($this->query['orderBy'])) {
                        $this->queryString .= sprintf(' ORDER BY %s', join(', ', $this->query['orderBy']));
                    }
                    if (isset($this->query['limit'])) {
                        $this->queryString .= sprintf(' LIMIT %d', $this->query['limit'][0]);
                    }
                }
            } elseif (isset($this->query['delete'])) {
                $agent = $this->connection->getAgent();

                $this->queryString = "DELETE FROM {$this->table}";
                if (isset($this->query['where'])) {
                    $this->queryString .= sprintf(' WHERE %s', join(' ', $this->query['where']));
                }
                if (isset($this->query['orderBy'])) {
                    $this->queryString .= sprintf(' ORDER BY %s', join(', ', $this->query['orderBy']));
                }
                if (isset($this->query['limit'])) {
                    $this->queryString .= sprintf(' LIMIT %d', $this->query['limit'][0]);
                }
            }

            $this->queryString = trim($this->queryString);
        }

        return $this->queryString;
    }

    final protected function push($key, $value, $multi = true) {
        if ($multi) {
            // Set query as sub array
            $this->query[$key][] = $value;
        } else {
            $this->query[$key] = $value;
        }

        return $this;
    }
}
