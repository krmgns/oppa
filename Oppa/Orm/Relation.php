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
 * @version    v1.1
 * @author     Kerem Gunes <qeremy@gmail>
 */
class Relation
{
    /**
     * Add select for child table(s) fields.
     *
     * @param  Oppa\Database\Query\Builder $query
     * @return Oppa\Database\Query\Builder
     */
    final protected function addSelect(QueryBuilder $query) {
        // child fields
        $fields = [];

        // use select options
        if (isset($this->relations['select'])) {
            // add parent table prefix
            $query->addPrefixTo('select', $this->table);

            foreach ($this->relations['select'] as $key => $value) {
                $key = strtoupper(trim($key));
                // add group by
                if ($key == 'GROUP BY') {
                    $query->groupBy($value);
                    continue;
                }

                // join tables
                foreach ($value as $i => $options) {
                    if ($key == 'JOIN') {
                        isset($options['using']) && $options['using'] == true
                            ? $query->joinUsing($options['table'], $options['foreign_key'])
                            : $query->join($options['table'], sprintf(
                                '%s.%s = %s.%s',
                                    $options['table'], $options['foreign_key'],
                                    $this->table, $this->primaryKey
                              ));
                    } elseif ($key == 'LEFT JOIN') {
                        isset($options['using']) && $options['using'] == true
                            ? $query->joinLeftUsing($options['table'], $options['foreign_key'])
                            : $query->joinLeft($options['table'], sprintf(
                                '%s.%s = %s.%s',
                                    $options['table'], $options['foreign_key'],
                                    $this->table, $this->primaryKey
                              ));
                    } // else { not supported yet! }

                    // add child fields
                    if (isset($options['fields'])) {
                        $fields = array_merge($fields,
                            $this->prepareFields($options['table'], $options['fields']));
                    }
                }
            }
        }

        // add child fields too
        $query->select($fields, false);

        return $query;
    }

    /**
     * Prepare fields appending table names.
     *
     * @param  string $table
     * @param  array  $fields
     * @return array
     */
    final private function prepareFields($table, $fields) {
        // check fields
        if (empty($fields)) {
            return [];
        }

        return array_map(function($field) use($table) {
            $field  = trim($field);
            // dotted?
            if (strstr($field, '.')) {
                return $field;
            }

            // function?
            if (strstr($field, '(')) {
                return preg_replace_callback('~(.+)\((.+?)\)(.*)~i', function($matches) use($table) {
                    // append table before field
                    return sprintf('%s(%s.%s)%s', $matches[1], $table, $matches[2], $matches[3]);
                }, $field);
            }

            // add dots
            return sprintf('%s.%s', $table, $field);
        }, $fields);
    }
}
