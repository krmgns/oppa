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

namespace Oppa\Shablon\Database\Connector\Agent;

/**
 * @package    Oppa
 * @subpackage Oppa\Shablon\Database\Connector\Agent
 * @object     Oppa\Shablon\Database\Connector\Agent\StreamFilterInterface
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
interface StreamFilterInterface
{
    /**
     * Action pattern.
     *
     * @param  string     $input
     * @param  array|null $params
     */
    public function prepare($input, array $params = null);

    /**
     * Action pattern.
     *
     * @param  string $input
     * @param  string $type
     */
    public function escape($input, $type = null);

    /**
     * Action pattern.
     *
     * @param string $input
     */
    public function escapeIdentifier($input);
}
