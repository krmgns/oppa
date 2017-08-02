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

use Oppa\Link\Linker;

/**
 * @package Oppa
 * @object  Oppa\Database
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Database
{
    /**
     * Database info. @wait
     * @var array
     */
    private $info;

    /**
     * Linker.
     * @var Oppa\Link\Linker
     */
    private $linker;

    /**
     * Linker methods.
     * @var array
     */
    private $linkerMethods = [];

    /**
     * Constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->linker = new Linker(new Config($config));
        // provide some speed instead using method_exists() each __call() exec
        $this->linkerMethods = array_fill_keys(get_class_methods($this->linker), true);
    }

    /**
     * Call magic (forwards all non-exists methods to Linker).
     * @see    Proxy pattern <https://en.wikipedia.org/wiki/Proxy_pattern>
     * @param  string $method
     * @param  array  $methodArgs
     * @return any
     * @throws \BadMethodCallException
     */
    public function __call(string $method, array $methodArgs = [])
    {
        if (isset($this->linkerMethods[$method])) {
            return call_user_func_array([$this->linker, $method], $methodArgs);
        }

        throw new \BadMethodCallException(sprintf(
            "No method such '%s()' on '%s' or '%s' objects!",
                $method, Database::class, Linker::class));
    }

    // @wait
    public function getInfo()
    {}

    /**
     * Get linker.
     * @return Oppa\Link\Linker
     */
    public function getLinker(): Linker
    {
        return $this->linker;
    }

    /**
     * Get linker methods.
     * @return array
     */
    public function getLinkerMethods(): array
    {
        return $this->linkerMethods;
    }
}
