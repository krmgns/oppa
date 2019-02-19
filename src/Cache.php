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

/**
 * @package Oppa
 * @object  Oppa\Cache
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Cache
{
    /**
     * Directory.
     * @var string
     */
    private $directory;

    /**
     * Constructor.
     * @param string $directory null
     */
    public function __construct(string $directory = null)
    {
        $this->setDirectory($directory ?? sys_get_temp_dir() .'/oppa');
    }

    /**
     * Set directory.
     * @param string $directory
     * @return void
     */
    public function setDirectory(string $directory): void
    {
        $this->directory = $directory;
    }

    /**
     * Read.
     * @param  string $file
     * @param  any    &$contents
     * @param  bool   $json
     * @param  int    $ttl
     * @return bool
     */
    public function read(string $file, &$contents = null, bool $json = true, int $ttl = 3600): bool
    {
        $this->checkDirectory() && $ok = $this->checkFile($file);

        if ($ok) { // file exists
            if (filemtime($file) < time() - $ttl) {
                $contents = null;

                unlink($file); // do gc

                return false;
            }
        }

        $contents = file_get_contents($file);
        if ($json && $contents !== false) {
            $contents = json_decode($contents, true);
        }

        return ($contents !== false);
    }

    /**
     * Write.
     * @param  string $file
     * @param  any    $contents
     * @param  bool   $json
     * @return bool
     */
    public function write(string $file, $contents, bool $json = true): bool
    {
        $this->checkDirectory() && $this->checkFile($file);

        if ($json) {
            $contents = json_encode($contents);
        }

        return (bool) file_put_contents($file, (string) $contents, LOCK_EX);
    }

    /**
     * Check directory.
     * @return bool
     */
    private function checkDirectory(): bool
    {
        return file_exists($this->directory) || mkdir($this->directory, 0700, true);
    }

    /**
     * Check file.
     * @param  string &$file
     * @return bool
     */
    private function checkFile(string &$file): bool
    {
        $file = "{$this->directory}/{$file}";

        return file_exists($file) || (touch($file) && chmod($file, 0600));
    }
}
