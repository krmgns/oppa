<?php
/**
 * Copyright (c) 2015 Kerem Gunes
 *    <http://qeremy.com>
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

namespace Oppa\Shablon\Logger;

/**
 * @package    Oppa
 * @subpackage Oppa\Shablon\Logger
 * @object     Oppa\Shablon\Logger\Logger
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
abstract class Logger
{
    /**
     * Log all events.
     * @const integer
     */
    const ALL = 30; // FAIL | WARN | INFO | DEBUG

    /**
     * Log only error events.
     * @const integer
     */
    const FAIL = 2;

    /**
     * Log only warning events.
     * @const integer
     */
    const WARN = 4;

    /**
     * Log only informal events.
     * @const integer
     */
    const INFO = 8;

    /**
     * Log only debugging events.
     * @const integer
     */
    const DEBUG = 16;

    /**
     * Log level, disabled as default.
     * @var integer
     */
    protected $level = 0;

    /**
     * Log directory.
     * @var string
     */
    protected $directory;

    /**
     * Aims some performance, escaping to call "is_dir" function.
     * @var boolean
     */
    protected $directoryChecked = false;

    /**
     * Log file format, e.g 2015-01-01.txt
     * @var string
     */
    protected $filenameFormat = 'Y-m-d';

    /**
     * Set log level.
     *
     * @param  integer $level  Must be a valid level like self::ALL
     * @return void
     */
    final public function setLevel($level) {
        $this->level = $level;
    }

    /**
     * Get log level.
     *
     * @return integer
     */
    final public function getLevel() {
        return $this->level;
    }

    /**
     * Set log directory.
     *
     * @param  string $directory
     * @return void
     */
    final public function setDirectory($directory) {
        $this->directory = $directory;
    }

    /**
     * Get log directory.
     *
     * @return string
     */
    final public function getDirectory() {
        return $this->directory;
    }

    /**
     * Check log directory, if not exists create it.
     *
     * @throws \RuntimeException
     * @return boolean
     */
    public function checkDirectory() {
        if (empty($this->directory)) {
            throw new \RuntimeException(
                'Log directory is not defined in given configuration! '.
                'Define it using `query_log_directory` key to activate logging.');
        }

        $this->directoryChecked = $this->directoryChecked ?: is_dir($this->directory);
        if (!$this->directoryChecked) {
            $this->directoryChecked = mkdir($this->directory, 0755, true);

            // !!! notice !!!
            // set your log dir secure
            file_put_contents($this->directory .'/index.php',
                "<?php header('HTTP/1.1 403 Forbidden'); ?>");
            // this action is for only apache, see nginx configuration here:
            // http://nginx.org/en/docs/http/ngx_http_access_module.html
            file_put_contents($this->directory .'/.htaccess',
                "Order deny,allow\r\nDeny from all");
        }

        return $this->directoryChecked;
    }

    /**
     * Set log filename format.
     *
     * @param  string $filenameFormat
     * @return void
     */
    final public function setFilenameFormat($filenameFormat) {
        $this->filenameFormat = $filenameFormat;
    }

    /**
     * Get log filename format.
     *
     * @return string
     */
    final public function getFilenameFormat() {
        return $this->filenameFormat;
    }

    /**
     * Action pattern.
     *
     * @param integer $level
     * @param string  $message
     */
    abstract public function log($level, $message);
}
