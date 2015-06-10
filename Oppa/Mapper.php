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

namespace Oppa;

use \Oppa\Exception as Exception;

/**
 * @package Oppa
 * @object  Oppa\Mapper
 * @uses    Oppa\Exception
 * @extends Oppa\Shablon\Mapper\Mapper
 * @version v1.1
 * @author  Kerem Gunes <qeremy@gmail>
 */

final class Mapper
    extends \Oppa\Shablon\Mapper\Mapper
{
    /**
     * Create a fresh Mapper object.
     *
     * @param array $options
     */
    public function __construct(array $options = []) {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Map given data by key.
     *
     * @param  string $key (table name actually, see: Oppa\Database\Query\Result\Mysqli:123)
     * @param  array  $data
     * @return array
     */
    final public function map($key, array $data) {
        // no map model for mapping
        if (empty($data) || empty($this->map) || !isset($this->map[$key])) {
            return $data;
        }

        // let's do it!
        foreach ($this->map[$key] as $fieldName => $fieldProperties) {
            foreach ($data as &$d) {
                // keep data type
                $dType = gettype($d);
                foreach ($d as $key => $value) {
                    // match field?
                    if ($key == $fieldName) {
                        if ($dType == 'array') {
                            $d[$key] = $this->cast($value, $fieldProperties);
                        } elseif ($dType == 'object') {
                            $d->{$key} = $this->cast($value, $fieldProperties);
                        }
                    }
                }
            }
        }

        return $data;
    }
}
