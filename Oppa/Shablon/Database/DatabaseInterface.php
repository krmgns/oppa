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

namespace Oppa\Shablon\Database;

/**
 * @package    Oppa
 * @subpackage Oppa\Shablon\Database
 * @object     Oppa\Shablon\Database\DatabaseInterface
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
interface DatabaseInterface
{
    /**
     * Action pattern.
     *
     * @param string $host
     */
    public function connect($host = null);

    /**
     * Action pattern.
     *
     * @param string $host
     */
    public function disconnect($host = null);

    /**
     * Action pattern.
     *
     * @param string $host
     */
    public function isConnected($host = null);

    /**
     * Action pattern.
     *
     * @param string $host
     */
    public function getConnection($host = null);

    /**
     * Action pattern.
     */
    public function info();
}
