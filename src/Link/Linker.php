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

namespace Oppa\Link;

use Oppa\{Util, Database, Config};
use Oppa\Exception\InvalidConfigException;

/**
 * @package Oppa
 * @object  Oppa\Link\Linker
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Linker
{
    /**
     * Link trait.
     * @object Oppa\Link\LinkTrait
     */
    use LinkTrait;

    /**
     * Database.
     * @var Oppa\Database
     */
    protected $database;

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
     * @param Oppa\Database $database
     * @param Oppa\Config   $config
     * @note  For all methods in this object, config `host` parameter is important,
     * cos it is used as a key to prevent to create new links in excessive way. Thus,
     * host will be always set, even user does not pass/provide it.
     */
    public function __construct(Database $database, Config $config)
    {
        $this->database = $database;
        $this->config = $config;
    }

    /**
     * Connect.
     * @param  string|null $host
     * @return self
     * @throws Oppa\Exception\InvalidConfigException
     */
    public function connect(string $host = null): self
    {
        // link is already active?
        if ($host && isset($this->links[$host])) {
            return $this;
        }

        // set type as single as default
        $type = Link::TYPE_SINGLE;

        // get config array, and database directives from config
        $config = $this->config->toArray();
        $database = $config['database'] ?? [];

        // is master/slave active?
        if ($this->config->get('sharding') === true) {
            $master = $database['master'] ?? [];
            $slaves = $database['slaves'] ?? [];
            switch ($host) {
                // master as default
                case null:
                case Link::TYPE_MASTER:
                    $type = Link::TYPE_MASTER;
                    $database = array_merge($database, $master);
                    break;
                //  slave
                case Link::TYPE_SLAVE:
                    $type = Link::TYPE_SLAVE;
                    if ($slaves != null) {
                        $database = array_merge($database, Util::arrayRand($slaves));
                    }
                    break;
                default:
                    // given host is master's host?
                    if ($host == ($master['host'] ?? '')) {
                        $type = Link::TYPE_MASTER;
                        $database = array_merge($database, $master);
                    } else {
                        // or given host is slaves's host?
                        $type = Link::TYPE_SLAVE;
                        foreach ($slaves as $slave) {
                            if (isset($slave['host']) && $slave['host'] == $host) {
                                $database = array_merge($database, $slave);
                                break;
                            }
                        }
                    }
            }
        }

        // remove unused parts
        unset($config['database'], $database['master'], $database['slaves']);

        // merge configs
        $config = array_merge($config, $database);
        if (!isset($config['host'], $config['name'], $config['username'], $config['password'])) {
            throw new InvalidConfigException(
                'Please specify all needed credentials (host, name, username, password) for a link!');
        }

        // use host as a key for link stack
        $host = $config['host'];

        // create a new link if not exists
        if (!isset($this->links[$host])) {
            $link = new Link($this->database, new Config($config), $type, $host);
            $link->open();
            // add link to links stack
            $this->setLink($host, $link);
        }

        return $this;
    }

    /**
     * Disconnect.
     * @param  string|null $host
     * @return void
     */
    public function disconnect(string $host = null): void
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
    public function isLinked(string $host = null): bool
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
    public function setLink(string $host, Link $link): void
    {
        $this->links[$host] = $link;
    }

    /**
     * Get link.
     * @param  string|null $host
     * @return ?Oppa\Link\Link
     */
    public function getLink(string $host = null): ?Link
    {
        // link exists?
        // e.g: getLink('localhost')
        if ($host && isset($this->links[$host])) {
            return $this->links[$host];
        }

        $host = trim((string) $host);
        // with master/slave directives
        if ($this->config->get('sharding') === true) {
            // e.g: getLink(), getLink('master'), getLink('master.mysql.local')
            if ($host == '' || $host == Link::TYPE_MASTER) {
                return Util::arrayRand(array_filter($this->links, function($link) {
                    return ($link->getType() == Link::TYPE_MASTER);
                }));
            }
            // e.g: getLink(), getLink('slave'), getLink('slave1.mysql.local')
            elseif ($host == Link::TYPE_SLAVE) {
                return Util::arrayRand(array_filter($this->links, function($link) {
                    return ($link->getType() == Link::TYPE_SLAVE);
                }));
            }
        } else {
            // e.g: getLink()
            if ($host == '') {
                return Util::arrayRand(array_filter($this->links, function($link) {
                    return ($link->getType() == Link::TYPE_SINGLE);
                }));
            }
        }
    }

    /**
     * Get config.
     * @return Oppa\Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Get links.
     * @return array
     */
    public function getLinks(): array
    {
        return $this->links;
    }
}
