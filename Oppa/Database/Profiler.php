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

namespace Oppa\Database;

use \Oppa\Exception;

/**
 * @package    Oppa
 * @subpackage Oppa\Database
 * @object     Oppa\Database\Factory
 * @uses       Oppa\Exception
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
final class Profiler
    extends \Oppa\Shablon\Database\Profiler\Profiler
{
    // final public function __construct() {}

    /**
     * Start profiling with given name.
     *
     * @param  integer $name
     * @return void
     */
    final public function start($name) {
        $this->profiles[$name] = [
            'start' => microtime(true),
            'stop'  => 0,
            'total' => 0
        ];
    }

    /**
     * Stop profiling with given name.
     *
     * @param  integer $name
     * @throws Oppa\Exception\ArgumentException
     * @return void
     */
    final public function stop($name) {
        if (!isset($this->profiles[$name])) {
            throw new Exception\ArgumentException(
                "Could not find a `{$name}` profile name!");
        }

        $this->profiles[$name]['stop'] = microtime(true);
        $this->profiles[$name]['total'] = number_format(
            (float) (
                $this->profiles[$name]['stop'] - $this->profiles[$name]['start']
            ), 10);
    }

    /**
     * Set property.
     *
     * @param  integer $name
     * @param  mixed   $value
     * @return void
     */
    final public function setProperty($name, $value = null) {
        // increase query count
        if ($name === self::PROP_QUERY_COUNT) {
            if (!isset($this->properties[self::PROP_QUERY_COUNT])) {
                $this->properties[self::PROP_QUERY_COUNT] = 0;
            }
            ++$this->properties[self::PROP_QUERY_COUNT];
        }
        // set property
        else {
            $this->properties[$name] = $value;
        }
    }

    /**
     * Get property.
     *
     * @param  integer $name
     * @throws Oppa\Exception\ArgumentException
     * @return mixed
     */
    final public function getProperty($name) {
        if (isset($this->properties[$name])) {
            return $this->properties[$name];
        }

        throw new Exception\ArgumentException('Undefined property name given!');
    }
}
