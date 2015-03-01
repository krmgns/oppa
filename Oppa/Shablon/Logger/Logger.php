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

/* defined here first, cos cannot define a class contant sometimes easily in it */
define(__namespace__.'\FAIL',  2);
define(__namespace__.'\WARN',  4);
define(__namespace__.'\INFO',  8);
define(__namespace__.'\DEBUG', 16);
define(__namespace__.'\ALL',   FAIL | WARN | INFO | DEBUG); // @WTF!

/**
 * @package    Oppa
 * @subpackage Oppa\Shablon\Logger
 * @object     Oppa\Orm
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
abstract class Logger
{
    const ALL   = ALL;
    const FAIL  = FAIL;
    const WARN  = WARN;
    const INFO  = INFO;
    const DEBUG = DEBUG;

    // disabled as default
    protected $level = 0;

    protected $directory;
    protected $directoryChecked = false;

    protected $filenameFormat = 'Y-m-d';

    public function setLevel($level) {
        $this->level = $level;
    }

    public function getLevel() {
        return $this->level;
    }

    public function setDirectory($directory) {
        $this->directory = $directory;
    }

    public function getDirectory() {
        return $this->directory;
    }

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
    }

    public function setFilenameFormat($filenameFormat) {
        $this->filenameFormat = $filenameFormat;
    }

    public function getFilenameFormat() {
        return $this->filenameFormat;
    }

    abstract public function log($level, $message);
}
