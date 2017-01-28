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
 * @object  Oppa\Autoload
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Autoload
{
    /**
     * Singleton stuff.
     * @var self
     */
    private static $instance;

    /**
     * Forbidding idle init & copy actions.
     */
    final private function __construct() {}
    final private function __clone() {}

    /**
     * Init.
     * @return self
     */
    final public static function init(): self
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register.
     * @return bool
     * @throws \RuntimeException
     */
    final public function register(): bool
    {
        return spl_autoload_register(function(string $objectName) {
            // ensure first namespace separator
            if ($objectName[0] != '\\') {
                $objectName = '\\'. $objectName;
            }

            // only Oppa stuff
            if (1 !== strpos($objectName, __namespace__)) {
                return;
            }

            $objectFile = strtr(sprintf('%s/%s.php', __dir__, $objectName), [
                '\\' => '/', __namespace__ => '',
            ]);

            if (!is_file($objectFile)) {
                throw new \RuntimeException("Class file not found. file: `{$objectFile}`");
            }

            require($objectFile);
        });
    }
}

// shorcut for require
return Autoload::init();
