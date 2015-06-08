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

namespace Oppa\Shablon\Mapper;

/**
 * @package    Oppa
 * @subpackage Oppa\Shablon\Mapper
 * @object     Oppa\Shablon\Mapper\Mapper
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
abstract class Mapper
{
    /**
     * Type constants.
     * @const string
     */
    const
        // int
        TYPE_INT       = 'int',
        TYPE_BIGINT    = 'bigint',
        TYPE_TINYINT   = 'tinyint',
        TYPE_SMALLINT  = 'smallint',
        TYPE_MEDIUMINT = 'mediumint',
        // float
        TYPE_FLOAT     = 'float',
        TYPE_DOUBLE    = 'double',
        TYPE_DECIMAL   = 'decimal',
        TYPE_REAL      = 'real';

    /**
     * Data map.
     * @var array
     */
    protected $map = [];

    /**
     * Mapping options.
     * @var array
     */
    protected $options = [
        'tiny2bool' => false // converts tinyints to booleans
    ];

    /**
     * Set map.
     * @param  array $map
     * @return void
     */
    final public function setMap(array $map) {
        $this->map = $map;
    }

    /**
     * Get map.
     * @return array
     */
    final public function getMap() {
        return $this->map;
    }

    /**
     * Simply type-cast by data type.
     *
     * @todo Add more types if you decide extend it, i.e: utc dates to local
     *       dates (but with cfg option that indicates local timezone).
     *
     * @param  mixed $value
     * @param  array $properties
     * @return mixed
     */
    final public function cast($value, array $properties) {
        // some speed?
        $nullable =& $properties['nullable'];

        /**
         * 1.000.000 iters
         * regexp-------7.442563
         * switch-------2.709796
         */
        switch (strtolower($properties['type'])) {
            // ints
            case self::TYPE_INT:
            case self::TYPE_BIGINT:
            case self::TYPE_SMALLINT:
            case self::TYPE_MEDIUMINT:
                $value = ($nullable && $value === null)
                    ? null : (int) $value;
                break;
            // tiny-it baby.. =)
            case self::TYPE_TINYINT:
                if ($nullable && $value === null) {
                    // pass
                } else {
                    $value = (int) $value;
                    if ($this->options['tiny2bool']
                        && $properties['length'] === 1    /* @important */
                        && ($value === 0 || $value === 1) /* @important */
                    ) {
                        $value = (bool) $value;
                    }
                }
                break;
            // floats
            case self::TYPE_FLOAT:
            case self::TYPE_DOUBLE:
            case self::TYPE_DECIMAL:
            case self::TYPE_REAL:
                $value = ($nullable && $value === null)
                    ? null : (float) $value;
                break;
        }

        return $value;
    }

    /**
     * Action pattern.
     *
     * @param  string $key
     * @param  array  $data
     * @return array
     */
    abstract public function map($key, array $data);
}
