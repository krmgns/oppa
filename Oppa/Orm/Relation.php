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

namespace Oppa\Orm;

use \Oppa\Database\Query\Builder as QueryBuilder;

/**
 * @package    Oppa
 * @subpackage Oppa\Orm
 * @object     Oppa\Orm\Relation
 * @uses       Oppa\Database\Query\Builder
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
class Relation
{
    final protected function generateSelectQuery() {
        $query = new QueryBuilder($this->getDatabase()->getConnection());
        $query->setTable($this->table);

        // parent fields
        $fields = $this->prepareFields($this->table, $this->selectFields);

        if (isset($this->relations['select'])) {
            foreach ($this->relations['select'] as $key => $value) {
                foreach ($value as $i => $options) {
                    $key = trim($key);
                    if ($key == 'join') {
                        isset($options['using']) && $options['using'] == true
                            ? $query->joinUsing($options['table'], $options['foreign_key'])
                            : $query->join($options['table'], sprintf(
                                '%s.%s = %s.%s',
                                    $options['table'], $options['foreign_key'],
                                    $this->table, $this->primaryKey
                              ));
                    } elseif ($key == 'left join') {
                        isset($options['using']) && $options['using'] == true
                            ? $query->joinLeftUsing($options['table'], $options['foreign_key'])
                            : $query->joinLeft($options['table'], sprintf(
                                '%s.%s = %s.%s',
                                    $options['table'], $options['foreign_key'],
                                    $this->table, $this->primaryKey
                              ));
                    } // else { no supported yet! }

                    $fieldPrefixFormat = isset($options['field_prefix'])
                        ? "%s AS {$options['field_prefix']}%s" : '';
                    foreach ($this->prepareFields($options['table'], $options['fields']) as $field) {
                        // field_prefix??
                        $fields[] = sprintf($fieldPrefixFormat, $field, $field);
                    }
                }
            }
            // select child table(s) fields too
            $query->select($fields, false);
        } else {
            // select parent table fields only
            $query->select($fields);
        }

        return $query;
    }

    final private function prepareFields($table, $fields) {
        static $escape;
        // !!! move escape works into query builder !!!
        !$escape && $escape = function($input) {
            return $input;
            // static $agent;
            // !$agent && $agent = $this->getDatabase()->getConnection()->getAgent();
            // return $agent->escapeIdentifier($input);
        };

        $table = $escape($table);
        return array_map(function($field) use($table, $escape) {
            $field  = trim($field);
            $hasDot = strpos($field, '.') !== false;
            $hasPrn = strpos($field, '(') !== false;

            // no function?
            if (!$hasPrn) {
                // dots?
                if ($hasDot) {
                    return $field = preg_replace_callback('~(.+)\.(.+)~',
                        function($matches) use($escape) {
                            return sprintf('%s.%s',
                                $escape($matches[1]),
                                $escape($matches[2]));
                        },
                    $field);
                }
                return sprintf('%s.%s', $table, $escape($field));
            }

            // handle function
            return preg_replace_callback('~(\w+)\s*\(\s*(.+?)\s*\)~i',
                function($matches) use($escape, $table) {
                    list(, $func, $field) = $matches;
                    // asterisk?
                    if ($field != '*') {
                        $field = $escape($field);
                    }

                    return sprintf('%s(%s.%s)', $func, $table, $field);
                },
            $field);
        }, $fields);
    }
}
