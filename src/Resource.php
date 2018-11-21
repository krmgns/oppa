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

namespace Oppa;

use Oppa\Exception\InvalidResourceException;

/**
 * @package Oppa
 * @object  Oppa\Resource
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Resource
{
    /**
     * Types.
     * @const int
     */
    public const TYPE_MYSQL_LINK   = 1,
                 TYPE_MYSQL_RESULT = 2,
                 TYPE_PGSQL_LINK   = 3,
                 TYPE_PGSQL_RESULT = 4;

    /**
     * Type.
     * @var ?int
     */
    private $type;

    /**
     * Object.
     * @var object|resource
     */
    private $object;

    /**
     * Constructor.
     * @param  object|resource $object
     * @throws Oppa\Exception\InvalidResourceException
     */
    public function __construct($object)
    {
        switch (gettype($object)) {
            // mysql
            case 'object':
                if ($object instanceof \mysqli) {
                    $this->type = self::TYPE_MYSQL_LINK;
                } elseif ($object instanceof \mysqli_result) {
                    $this->type = self::TYPE_MYSQL_RESULT;
                }
                break;
            // pgsql
            case 'resource':
                $type = get_resource_type($object);
                if ($type == 'pgsql link') {
                    $this->type = self::TYPE_PGSQL_LINK;
                } elseif ($type == 'pgsql result') {
                    $this->type = self::TYPE_PGSQL_RESULT;
                }
                break;
            // unknown
            default:
                throw new InvalidResourceException('Unknown resource type!');
        }

        $this->object = $object;
    }

    /**
     * Get type.
     * @return ?int
     */
    public function getType(): ?int
    {
        return $this->type;
    }

    /**
     * Get object.
     * @return object|resource
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Is valid.
     * @return bool
     */
    public function isValid(): bool
    {
        return !!($this->type && $this->object);
    }

    /**
     * Close.
     * @return void
     */
    public function close(): void
    {
        if ($this->type == self::TYPE_MYSQL_LINK) {
            $this->object->close();
        } elseif ($this->type == self::TYPE_PGSQL_LINK) {
            pg_close($this->object);
        }

        $this->reset();
    }

    /**
     * Free.
     * @return void
     */
    public function free(): void
    {
        if ($this->type == self::TYPE_MYSQL_RESULT) {
            $this->object->free();
        } elseif ($this->type == self::TYPE_PGSQL_RESULT) {
            pg_free_result($this->object);
        }

        $this->reset();
    }

    /**
     * Reset.
     * @return void
     */
    private function reset(): void
    {
        $this->type = null;
        $this->object = null;
    }
}
