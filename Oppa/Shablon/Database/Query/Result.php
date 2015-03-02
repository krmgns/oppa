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

namespace Oppa\Shablon\Database\Query;

/**
 * @package    Oppa
 * @subpackage Oppa\Shablon\Database\Query
 * @object     Oppa\Shablon\Database\Query\Result
 * @implements \Countable, \IteratorAggregate
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
abstract class Result
    implements \Countable, \IteratorAggregate
{
    /** Action pattern. */
    abstract public function free();

    /** Action pattern. */
    abstract public function reset();

    /**
     * Action pattern.
     *
     * @param object         $link
     * @param object|boolean $result
     * @param integer        $limit
     * @param string         $fetchType
     */
    abstract public function process($link, $result, $limit = null, $fetchType = null);
}
