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

namespace Oppa\Database\Query;

/**
 * @package    Oppa
 * @subpackage Oppa\Database\Query
 * @object     Oppa\Database\Query\Sql
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
final class Sql
{
    /**
     * Keeps raw SQL statement.
     * @var string
     */
    protected $query;

    /**
     * Create a fresh Sql object.
     *
     * Notice: This object is used for only to prevent escaping
     * contents like NOW(), COUNT(x) etc. in agent.escape() methods.
     * Nothing more..
     *
     * @param string $query
     */
    final public function __construct($query) {
        $this->query = trim($query);
    }

    /**
     * Get SQL statement.
     *
     * @return string
     */
    final public function toString() {
        return $this->query;
    }
}
