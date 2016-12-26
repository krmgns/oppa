<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *    <k-gun@mail.com>
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
declare(strict_types=1);

namespace Oppa\Batch;

/**
 * @package    Oppa
 * @subpackage Oppa\Batch
 * @object     Oppa\Batch\BatchInterface
 * @author     Kerem Güneş <k-gun@mail.com>
 */
interface BatchInterface
{
    /**
     * Lock.
     * @return bool
     */
    public function lock(): bool;

    /**
     * Unlock.
     * @return bool
     */
    public function unlock(): bool;

    /**
     * Do.
     * @return Oppa\Batch\BatchInterface
     */
    public function do(): BatchInterface;

    /**
     * Do query.
     * @param  string     $query
     * @param  array|null $params
     * @return Oppa\Batch\BatchInterface
     */
    public function doQuery(string $query, array $params = null): BatchInterface;

    /**
     * Undo.
     * @return void
     */
    public function undo(): void;
}
