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

use \Oppa\Configuration;

/**
 * @package    Oppa
 * @subpackage Oppa\Database
 * @object     Oppa\Database\Factory
 * @uses       Oppa\Configuration
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
final class Factory
{
    /**
     * Build a fresh Database object.
     *
     * @param  Oppa\Configuration $configuration
     * @return Oppa\Database
     */
    final static public function build(Configuration $configuration) {
        return \Oppa\Factory::build('\Oppa\Database', [$configuration]);
    }
}
