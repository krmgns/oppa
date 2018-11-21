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

namespace Oppa\Batch;

use Oppa\Agent;

/**
 * @package Oppa
 * @object  Oppa\Batch\Pgsql
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Pgsql extends Batch
{
    /**
     * Constructor.
     * @param Oppa\Agent\Pgsql $agent
     */
    public function __construct(Agent\Pgsql $agent)
    {
        $this->agent = $agent;
    }

    /**
     * Lock.
     * @return bool
     */
    public function lock(): bool
    {
        return true;
    }

    /**
     * Unlock.
     * @return bool
     */
    public function unlock(): bool
    {
        return true;
    }

    /**
     * Start.
     * @return void
     */
    protected function start(): void
    {
        pg_query($this->agent->getResource()->getObject(), 'BEGIN');
    }

    /**
     * End.
     * @return void
     */
    protected function end(): void
    {
        pg_query($this->agent->getResource()->getObject(), 'COMMIT');
    }

    /**
     * Undo.
     * @return void
     */
    public function undo(): void
    {
        // mayday mayday
        pg_query($this->agent->getResource()->getObject(), 'ROLLBACK');

        $this->reset();
    }
}
