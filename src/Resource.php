<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
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
