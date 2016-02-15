<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *   <k-gun@mail.com>
 *
 * GNU General Public License v3.0
 *   <http://www.gnu.org/licenses/gpl-3.0.txt>
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
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Sql
{
   /**
    * Keeps raw SQL string.
    * @var string
    */
   protected $query;

   /**
    * Object constructor.
    *
    * Notice: This object is used for only to prevent escaping
    * contents like NOW(), COUNT() etc. in agent.escape() methods.
    * Nothing more..
    *
    * @param string $query
    */
   final public function __construct($query)
   {
      $this->query = trim($query);
   }

   /**
    * Get SQL string.
    *
    * @return string
    */
   final public function __toString()
   {
      return $this->toString();
   }

   /**
    * Get SQL string.
    *
    * @return string
    */
   final public function toString()
   {
      return $this->query;
   }
}
