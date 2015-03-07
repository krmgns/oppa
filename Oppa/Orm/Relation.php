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
                    } // else { not supported yet! }

                    // prepare select field format
                    $fieldPrefixFormat = '%s';
                    if (isset($options['field_prefix']) && trim($options['field_prefix'])) {
                        $fieldPrefixFormat = '%s AS '. substr(
                            $options['field_prefix'], strpos($options['field_prefix'], '.'));
                    }

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
        return array_map(function($field) use($table) {
            $field  = trim($field);
            $hasDot = strpos($field, '.') !== false;
            $hasPrn = strpos($field, '(') !== false;

            // no function?
            if (!$hasPrn) {
                // dots?
                if ($hasDot) {
                    return preg_replace_callback('~(.+)\.(.+)~', function($matches) {
                        return sprintf('%s.%s', $matches[1], $matches[2]);
                    }, $field);
                }
                return sprintf('%s.%s', $table, $field);
            }

            // handle function
            return preg_replace_callback('~(\w+)\s*\(\s*(.+?)\s*\)~i', function($matches) use($table) {
                return sprintf('%s(%s.%s)', $matches[1], $table, $matches[2]);
            }, $field);
        }, $fields);
    }
}
