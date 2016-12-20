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

namespace Oppa\Link;

use Oppa\{Util, Config};
use Oppa\Exception\InvalidConfigException;

/**
 * @package    Oppa
 * @subpackage Oppa\Link
 * @object     Oppa\Link\Linker
 * @author     Kerem Güneş <k-gun@mail.com>
 */
final class Linker
{
    /**
     * Config.
     * @var Oppa\Config
     */
    protected $config;

    /**
     * Stack.
     * @var array
     */
    protected $links = [];

    /**
     * Constructor.
     * @note  For all methods in this object, "$host" parameter is important, cos
     * it is used as a key to prevent to create new links in excessive way.
     * Thus, host will be always set, even user does not pass/provide it.
     * @param Oppa\Config $config
     */
    final public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Connect.
     * @param  string|null $host
     * @return self
     * @throws Oppa\InvalidConfigException
     */
    final public function connect(string $host = null): self
    {
        // link is already active?
        if ($host && isset($this->links[$host])) {
            return $this;
        }

        // set type as single as default
        $type = Link::TYPE_SINGLE;

        // get config as array
        $config = $this->config->toArray();

        // get database directives from given config
        $database = ($config['database'] ?? []);

        // is master/slave active?
        if (true === $this->config->get('sharding')) {
            $master = ($database['master'] ?? []);
            $slaves = ($database['slaves'] ?? []);
            switch ($host) {
                // act: master as default
                case null:
                case Link::TYPE_MASTER:
                    $type = Link::TYPE_MASTER;
                    $database = $database + $master;
                    break;
                //  act: slave
                case Link::TYPE_SLAVE:
                    $type = Link::TYPE_SLAVE;
                    if (!empty($slaves)) {
                        $slave = Util::arrayRand($slaves);
                        $database = $database + $slave;
                    }
                    break;
                default:
                    // given host is master's host?
                    if ($host == ($master['host'] ?? '')) {
                        $type = Link::TYPE_MASTER;
                        $database = $database + $master;
                    } else {
                        // or given host is slaves's host?
                        $type = Link::TYPE_SLAVE;
                        foreach ($slaves as $slave) {
                            if (isset($slave['host'], $slave['name']) && $slave['host'] == $host) {
                                $database = $database + $slave;
                                break;
                            }
                        }
                    }
            }
        }

        // remove unused parts
        unset($config['database'], $database['master'], $database['slaves']);

        // merge configs
        $config = $config + (array) $database;
        if (!isset($config['host'], $config['name'], $config['username'], $config['password'])) {
            throw new InvalidConfigException(
                'Please specify all needed credentials (host'.
                ', name, username, password) for a link!'
            );
        }

        // use host as a key for link stack
        $host = $config['host'];

        // create a new link if not exists
        if (!isset($this->links[$host])) {
            $link = new Link($type, $host, new Config($config));
            $link->open();
            $this->setLink($host, $link);
        }

        return $this;
    }

    /**
     * Disconnect.
     * @param  string|null $host
     * @return void
     */
    final public function disconnect(string $host = null): void
    {
        // link exists?
        if ($host && isset($this->links[$host])) {
            $this->links[$host]->close();
            unset($this->links[$host]);
        } else {
            // check by host
            switch (trim((string) $host)) {
                // remove all links
                case '':
                case '*':
                    foreach ($this->links as $i => $link) {
                        $link->close();
                        unset($this->links[$i]);
                    }
                    break;
                // remove master link
                case Link::TYPE_MASTER:
                    foreach ($this->links as $i => $link) {
                        if ($link->getType() == Link::TYPE_MASTER) {
                            $link->close();
                            unset($this->links[$i]);
                            break;
                        }
                    }
                    break;
                // remove slave links
                case Link::TYPE_SLAVE:
                    foreach ($this->links as $i => $link) {
                        if ($link->getType() == Link::TYPE_SLAVE) {
                            $link->close();
                            unset($this->links[$i]);
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Is linked.
     * @param  string|null $host
     * @return bool
     */
    final public function isLinked(string $host = null): bool
    {
        // link exists?
        // e.g: isLinked('localhost')
        if ($host && isset($this->links[$host])) {
            return ($this->links[$host]->status() === Link::STATUS_CONNECTED);
        }

        // without master/slave directives
        // e.g: isLinked()
        if (true !== $this->config->get('sharding')) {
            foreach ($this->links as $link) {
                return ($link->status() === Link::STATUS_CONNECTED);
            }
        }

        // with master/slave directives, check by host
        switch (trim((string) $host)) {
            // e.g: isLinked(), isLinked('master')
            case '':
            case Link::TYPE_MASTER:
                foreach ($this->links as $link) {
                    if ($link->getType() == Link::TYPE_MASTER) {
                        return ($link->status() === Link::STATUS_CONNECTED);
                    }
                }
                break;
            // e.g: isLinked('slave1.mysql.local'), isLinked('slave')
            case Link::TYPE_SLAVE:
                foreach ($this->links as $link) {
                    if ($link->getType() == Link::TYPE_SLAVE) {
                        return ($link->status() === Link::STATUS_CONNECTED);
                    }
                }
                break;
        }

        return false;
    }

    /**
     * Set link.
     * @param  string         $host
     * @param  Oppa\Link\Link $link
     * @return void
     */
    final public function setLink(string $host, Link $link): void
    {
        $this->links[$host] = $link;
    }

    /**
     * Get link.
     * @param  string|null $host
     * @return ?Oppa\Link\Link
     */
    final public function getLink(string $host = null): ?Link
    {
        // link exists?
        // e.g: getLink('localhost')
        if ($host && isset($this->links[$host])) {
            return $this->links[$host];
        }

        $host = trim((string) $host);
        // with master/slave directives
        if (true === $this->config->get('sharding')) {
            // e.g: getLink(), getLink('master'), getLink('master.mysql.local')
            if ($host == '' || $host == Link::TYPE_MASTER) {
                return Util::arrayRand(
                    array_filter($this->links, function($link) {
                        return $link->getType() == Link::TYPE_MASTER;
                }));
            }
            // e.g: getLink(), getLink('slave'), getLink('slave1.mysql.local')
            elseif ($host == Link::TYPE_SLAVE) {
                return Util::arrayRand(
                    array_filter($this->links, function($link) {
                        return $link->getType() == Link::TYPE_SLAVE;
                }));
            }
        } else {
            // e.g: getLink()
            if ($host == '') {
                return Util::arrayRand(
                    array_filter($this->links, function($link) {
                        return $link->getType() == Link::TYPE_SINGLE;
                }));
            }
        }
    }

    /**
     * Get config.
     * @return Oppa\Config
     */
    final public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Get links.
     * @return array
     */
    final public function getLinks(): array
    {
        return $this->links;
    }
}
