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

namespace Oppa;

/**
 * @package Oppa
 * @object  Oppa\Util
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Util
{
    /**
     * Shuffle array and get a random value.
     * @param  array $array
     * @param  any   $valueDefault
     * @return any
     */
    final public static function arrayRand(array $array, $valueDefault = null)
    {
        shuffle($array);

        return $array[0] ?? $valueDefault;
    }

    /**
     * Convert given input from uppers to underscore.
     * @param  string $input
     * @return string
     */
    final public static function upperToSnake(string $input): string
    {
        return preg_replace_callback('~([A-Z])~', function($m) {
            return '_'. strtolower($m[1]);
        }, $input);
    }
}
