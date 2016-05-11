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
 * @object  Oppa\Factory
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Factory implements \Oppa\Shablon\FactoryInterface
{
    /**
     * Build an internal Oppa object.
     * @param  string $className
     * @param  array  $arguments
     * @return object
     * @throws \RuntimeException
     */
    final public static function build($className, array $arguments = null)
    {
        // ensure namespace separator
        if ($className[0] != '\\') {
            $className = '\\'. $className;
        }

        // ensure Oppa namespace
        if (0 !== strpos($className, '\Oppa')) {
            $className = '\Oppa' . $className;
        }

        // autoload
        if (!class_exists($className, true)) {
            throw new \RuntimeException(sprintf(
                '`%s` class does not exists!', $className));
        }

        return (new \ReflectionClass($className))
            ->newInstanceArgs($arguments);
    }
}
