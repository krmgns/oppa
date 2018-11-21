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

use Oppa\Link\Linker;

/**
 * @package Oppa
 * @object  Oppa\Database
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Database
{
    /**
     * Info. @wait
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
     * @link   Proxy pattern <https://en.wikipedia.org/wiki/Proxy_pattern>
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
