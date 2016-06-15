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

use Oppa\Link\Connector;

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
     * Connector.
     * @var Oppa\Link\Connector
     */
    private $connector;

    /**
     * Connector methods.
     * @var array
     */
    private $connectorMethods = [];

    /**
     * Constructor.
     * @param array $config
     */
    final public function __construct(array $config)
    {
        $this->connector = new Connector(new Config($config));
        // provide some speed instead using method_exists() each __call() exec
        $this->connectorMethods = array_fill_keys(get_class_methods($this->connector), true);
    }

    /**
     * Call magic (forwards all non-exists methods to Connector).
     * @param  string $method
     * @param  array  $methodArgs
     * @return any
     * @throws \BadMethodCallException
     */
    final public function __call(string $method, array $methodArgs = [])
    {
        if (isset($this->connectorMethods[$method])) {
            return call_user_func_array([$this->connector, $method], $methodArgs);
        }

        throw new \BadMethodCallException(sprintf(
            "No method such '%s()' on '%s' or '%s' objects!",
                $method, Database::class, Connector::class));
    }

    // @wait
    final public function getInfo()
    {}

    /**
     * Get connector.
     * @return Oppa\Link\Connector
     */
    final public function getConnector(): Connector
    {
        return $this->connector;
    }

    /**
     * Get connector methods.
     * @return array
     */
    final public function getConnectorMethods(): array
    {
        return $this->connectorMethods;
    }
}
