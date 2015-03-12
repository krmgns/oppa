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

namespace Oppa;

use \Oppa\Exception as Exception;

/**
 * @package Oppa
 * @object  Oppa\Logger
 * @uses    Oppa\Exception
 * @extends Oppa\Shablon\Logger\Logger
 * @version v1.1
 * @author  Kerem Gunes <qeremy@gmail>
 */

final class Logger
    extends \Oppa\Shablon\Logger\Logger
{
    /**
     * Log given message by level.
     *
     * @param  integer $level   Only available ALL, FAIL, WARN, INFO, DEBUG
     * @param  string  $message
     * @return boolean
     */
    final public function log($level, $message) {
        // no log command
        if (!$level || ($level & $this->level) == 0) {
            return;
        }

        // ensure log directory
        $this->checkDirectory();

        // prepare message prepend
        $messagePrepend = '';
        switch ($level) {
            case self::FAIL:
                $messagePrepend = '[FAIL] ';
                break;
            case self::INFO:
                $messagePrepend = '[INFO] ';
                break;
            case self::WARN:
                $messagePrepend = '[WARN] ';
                break;
            case self::DEBUG:
                $messagePrepend = '[DEBUG] ';
                break;
        }

        // prepare filename
        $filename = sprintf('%s/%s.log',
            $this->directory, date($this->filenameFormat));
        // prepare message
        $message  = sprintf('[%s] %s%s',
            date('D, d M Y H:i:s O'), $messagePrepend, trim($message) ."\n");

        return (bool) file_put_contents($filename, $message, LOCK_EX | FILE_APPEND);
    }
}
