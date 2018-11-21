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
 * @object  Oppa\Util
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final /* static */ class Util
{
    /**
     * Array rand.
     * @param  array $array
     * @param  any   $value
     * @return any
     */
    public static function arrayRand(array $array, $value = null)
    {
        shuffle($array);

        return $array[0] ?? $value;
    }

    /**
     * Array pick.
     * @param  array      &$array
     * @param  int|string $key
     * @param  any        $value
     * @return any
     */
    public static function arrayPick(array &$array, $key, $value = null)
    {
        if (array_key_exists($key, $array)) {
            $value = $array[$key] ?? $value;
            unset($array[$key]);
        }

        return $value;
    }

    /**
     * Convert given input from uppers to underscore.
     * @param  string $input
     * @return string
     */
    public static function upperToSnake(string $input): string
    {
        return preg_replace_callback('~([A-Z])~', function($m) {
            return '_'. strtolower($m[1]);
        }, $input);
    }

    /**
     * Get IP.
     * @return string
     */
    public static function getIp(): string
    {
        $ip = 'unknown';
        if (null != ($ip = ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))) {
            if (false !== strpos($ip, ',')) {
                $ip = trim((string) end(explode(',', $ip)));
            }
        }
        // all ok
        elseif (null != ($ip = ($_SERVER['HTTP_CLIENT_IP'] ?? ''))) {}
        elseif (null != ($ip = ($_SERVER['HTTP_X_REAL_IP'] ?? ''))) {}
        elseif (null != ($ip = ($_SERVER['REMOTE_ADDR_REAL'] ?? ''))) {}
        elseif (null != ($ip = ($_SERVER['REMOTE_ADDR'] ?? ''))) {}

        return $ip;
    }

    /**
     * Generate deprecated message.
     * @param  string|object   $class
     * @param  string          $oldStuff
     * @param  string          $newStuff
     * @return void
     */
    public static function generateDeprecatedMessage($class, string $oldStuff, string $newStuff): void
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        user_error(sprintf('%1$s::%2$s is deprecated, use %1$s::%3$s instead!',
            $class, $oldStuff, $newStuff), E_USER_DEPRECATED);
    }
}
