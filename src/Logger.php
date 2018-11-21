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

use Oppa\Exception\InvalidConfigException;

/**
 * @package Oppa
 * @object  Oppa\Logger
 * @author  Kerem Güneş <k-gun@mail.com>
 */
final class Logger
{
    /**
     * Levels.
     * @const int
     */
    public const ALL = 30, // FAIL | WARN | INFO | DEBUG,
                 FAIL = 2,
                 WARN = 4,
                 INFO = 8,
                 DEBUG = 16;

    /**
     * Level.
     * @var int
     */
    private $level = 0; // default=disabled

    /**
     * Directory.
     * @var string
     */
    private $directory;

    /**
     * Directory checked.
     * @var bool
     */
    private static $directoryChecked = false;

    /**
     * Constructor.
     */
    public function __construct() {}

    /**
     * Set level.
     * @param  int $level
     * @return void
     */
    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    /**
     * Get level.
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * Set directory.
     * @param  string $directory
     * @return void
     */
    public function setDirectory(string $directory): void
    {
        $this->directory = $directory;
    }

    /**
     * Get directory.
     * @return ?string
     */
    public function getDirectory(): ?string
    {
        return $this->directory;
    }

    /**
     * Check directory (if not exists create it).
     * @return bool
     * @throws Oppa\Exception\InvalidConfigException
     */
    public function checkDirectory(): bool
    {
        if (empty($this->directory)) {
            throw new InvalidConfigException(
                "Log directory is not defined in given configuration, ".
                "define it using 'query_log_directory' key to activate logging.");
        }

        // provide some performance escaping to call "is_dir" and "mkdir" functions
        self::$directoryChecked = self::$directoryChecked ?: is_dir($this->directory);
        if (!self::$directoryChecked) {
            self::$directoryChecked = mkdir($this->directory, 0644, true);

            // !!! notice !!!
            // set your log dir secure
            file_put_contents($this->directory .'/index.php',
                "<?php header('HTTP/1.1 403 Forbidden'); ?>");
            // this action is for only apache, see nginx configuration here:
            // http://nginx.org/en/docs/http/ngx_http_access_module.html
            file_put_contents($this->directory .'/.htaccess',
                "Order deny,allow\r\nDeny from all");
        }

        return self::$directoryChecked;
    }

    /**
     * Log.
     * @param  int    $level
     * @param  string $message
     * @return void|bool
     */
    public function log(int $level, string $message)
    {
        // no log command
        if (!$level || ($level & $this->level) == 0) {
            return;
        }

        // ensure log directory
        $this->checkDirectory();

        // prepare message prepend
        $levelText = '';
        switch ($level) {
            case self::FAIL:
                $levelText = 'FAIL';
                break;
            case self::INFO:
                $levelText = 'INFO';
                break;
            case self::WARN:
                $levelText = 'WARN';
                break;
            case self::DEBUG:
                $levelText = 'DEBUG';
                break;
        }

        // prepare message & message file
        $message = sprintf('[%s] %s >> %s', $levelText, date('D, d M Y H:i:s O'), trim($message) ."\n");
        $messageFile = sprintf('%s/%s.log', $this->directory, date('Y-m-d'));

        return error_log($message, 3, $messageFile);
    }
}
