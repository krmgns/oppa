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
 * @object  Oppa\Autoload
 * @version v1.0
 * @author  Kerem Gunes <qeremy@gmail>
 */
final class Autoload
{
    /**
     * Singleton stuff.
     * @var self
     */
    private static $instance;

    /**
     * Forbidding idle initializations.
     */
    final private function __construct() {}
    final private function __clone() {}

    /**
     * Create a fresh Autoload object or just return if already exits.
     *
     * @return self
     */
    final public static function initialize() {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register SPL Autoload.
     *
     * @throws \RuntimeException
     * @return mixed Load action results.
     */
    final public function register() {
        spl_autoload_register(function($objectName) {
            // ensure namespace separator
            $objectName = '\\'. ltrim($objectName, '\\');

            $objectRoot = '/Oppa/';
            $objectFile = str_replace('\\', '/', $objectName);
            // load only Oppa classes / interfaceses
            if (strstr($objectFile, $objectRoot) === false) {
                return;
            }

            $objectFile = sprintf('%s/%s.php', __dir__, substr($objectFile, strlen($objectRoot)));
            if (!is_file($objectFile)) {
                throw new \RuntimeException("Class file not found. file: `{$objectFile}`");
            }
            if (!is_readable($objectFile)) {
                throw new \RuntimeException("Class file is not readable. file: `{$objectFile}`");
            }

            $require = require($objectFile);

            // check: interface name is same with filaname?
            if (strripos($objectName, 'interface') !== false) {
                if (!interface_exists($objectName, false)) {
                    throw new \RuntimeException(
                        "Interface file `{$objectFile}` has been loaded but no " .
                        "interface found such as `{$objectName}`.");
                }

                return $require;
            }

            // check: trait name is same with filaname?
            if (strripos($objectName, 'trait') !== false) {
                if (!trait_exists($objectName, false)) {
                    throw new \RuntimeException(
                        "Trait file `{$objectFile}` has been loaded but no " .
                        "trait found such as `{$objectName}`.");
                }

                return $require;
            }

            // check: class name is same with filaname?
            if (!class_exists($objectName, false)) {
                throw new \RuntimeException(
                    "Class file `{$objectFile}` has been loaded but no " .
                    "class found such as `{$objectName}`.");
            }

            return $require;
        });
    }
}

// init autoload object as shorcut for require/include actions
return Autoload::initialize();
