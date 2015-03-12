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

use \Oppa\Exception\Database as Exception;

/**
 * @package    Oppa
 * @subpackage Oppa\Database
 * @object     Oppa\Database\Factory
 * @uses       Oppa\Exception\Database
 * @version    v1.1
 * @author     Kerem Gunes <qeremy@gmail>
 */
final class Profiler
    extends \Oppa\Shablon\Database\Profiler\Profiler
{
    /**
     * Start profiling with given key.
     *
     * @param  integer $key
     * @return void
     */
    final public function start($key) {
        $this->profiles[$key] = [
            'start' => microtime(true),
            'stop'  => 0,
            'total' => 0
        ];
    }

    /**
     * Stop profiling with given key.
     *
     * @param  integer $key
     * @throws Oppa\Exception\Database\ArgumentException
     * @return void
     */
    final public function stop($key) {
        if (!isset($this->profiles[$key])) {
            throw new Exception\ArgumentException(
                "Could not find a `{$key}` profile key!");
        }

        $this->profiles[$key]['stop'] = microtime(true);
        $this->profiles[$key]['total'] = number_format(
            (float) ($this->profiles[$key]['stop'] - $this->profiles[$key]['start'])
        , 10);
    }
}
