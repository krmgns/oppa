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

/**
 * @package Oppa
 * @object  Oppa\Helper
 * @version v1.0
 * @author  Kerem Gunes <qeremy@gmail>
 */
final class Helper
{
    /**
     * Get value from an array by key if exists or return default value.
     *
     * @param  string $key
     * @param  array  $array
     * @param  mixed  $defaultValue
     * @return mixed
     */
    final public static function getArrayValue($key, array $array, $defaultValue = null) {
        return isset($array[$key]) ? $array[$key] : $defaultValue;
    }

    /**
     * Shuffle array and get a random value.
     *
     * @param  array  $array
     * @param  mixed  $defaultValue
     * @return mixed
     */
    final public static function getArrayValueRandom(array $array, $defaultValue = null) {
        shuffle($array);
        return isset($array[0]) ? $array[0] : $defaultValue;
    }

    /**
     * Convert given input from underscore to camelcase.
     *
     * @param  string $input
     * @return string
     */
    final public static function underscoreToCamelcase($input) {
        return preg_replace_callback('~_([A-Za-z])~', function($m) {
            return strtoupper($m[1]);
        }, $input);
    }

    /**
     * Convert given input from camelcase to underscore.
     *
     * @param  string $input
     * @return string
     */
    final public static function camelcaseToUnderscore($input) {
        return preg_replace_callback('~([A-Z])~', function($m) {
            return '_'. strtolower($m[1]);
        }, $input);
    }
}
