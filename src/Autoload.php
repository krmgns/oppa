<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
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
    private function __construct() {}
    private function __clone() {}

    /**
     * Init.
     * @return self
     */
    public static function init(): self
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
    public function register(): bool
    {
        return spl_autoload_register(function($objectName) {
            // ensure first namespace separator
            if ($objectName[0] != '\\') {
                $objectName = '\\'. $objectName;
            }

            // only Oppa stuff
            if (1 !== strpos($objectName, __namespace__)) {
                return;
            }

            $objectFile = strtr(sprintf('%s/%s.php', __dir__, $objectName), [
                '\\' => '/',
                __namespace__ => ''
            ]);

            if (!is_file($objectFile)) {
                throw new \RuntimeException("Object file '{$objectFile}' not found!");
            }

            require $objectFile;
        });
    }
}

// shorcut for require
return Autoload::init();
