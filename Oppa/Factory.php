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
 * @package    Oppa
 * @object     Oppa\Factory
 * @implements Oppa\Shablon\FactoryInterface
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
final class Factory
    implements \Oppa\Shablon\FactoryInterface
{
    /**
     * Build an internal Oppa object.
     *
     * @param  string     $className
     * @param  array|null $arguments
     * @throws \RuntimeException
     * @return object
     */
    final public static function build($className, array $arguments = null) {
        // ensure namespace separator
        $className = '\\'. ltrim($className, '\\');

        // ensure Oppa namespace
        if (strpos($className, '\Oppa') !== 0) {
            $className = '\Oppa' . $className;
        }

        // try autoload
        if (!class_exists($className, true)) {
            throw new \RuntimeException(sprintf(
                '`%s` class does not exists!', $className));
        }

        // some performance escaping reflection class? :P
        switch (count($arguments)) {
            case 0: return new $className();
            case 1: return new $className($arguments[0]);
        }

        return (new \ReflectionClass($className))
            ->newInstanceArgs($arguments);
    }
}
