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

namespace Oppa;

/**
 * @package Oppa
 * @object  Oppa\Mapper
 * @author  Kerem Güneş <k-gun@mail.com>
 */

final class Mapper
{
    /**
     * Type constants.
     * @const string
     */
    public const
        // int
        TYPE_INT        = 'int',
        TYPE_BIGINT     = 'bigint',
        TYPE_TINYINT    = 'tinyint',
        TYPE_SMALLINT   = 'smallint',
        TYPE_MEDIUMINT  = 'mediumint',
        // float
        TYPE_FLOAT      = 'float',
        TYPE_DOUBLE     = 'double',
        TYPE_DECIMAL    = 'decimal',
        TYPE_REAL       = 'real';

    /**
     * Map.
     * @var array
     */
    protected $map = [];

    /**
     * Map options.
     * @var array
     */
    protected $mapOptions = [
        'tiny2bool' => false, // converts tinyints to booleans
    ];

    /**
     * Constructor.
     * @param array $mapOptions
     */
    final public function __construct(array $mapOptions = [])
    {
        $this->setMapOptions($mapOptions);
    }

    /**
     * Set map.
     * @param  array $map
     * @return void
     */
    final public function setMap(array $map): void
    {
        $this->map = $map;
    }

    /**
     * Get map.
     * @return array
     */
    final public function getMap(): array
    {
        return $this->map;
    }

    /**
     * Set map options.
     * @param  array $mapOptions
     * @return void
     */
    final public function setMapOptions(array $mapOptions): void
    {
        $this->mapOptions = array_merge($this->mapOptions, $mapOptions);
    }

    /**
     * Get map options.
     * @return array
     */
    final public function getMapOptions(): array
    {
        return $this->mapOptions;
    }

    /**
     * Map given data by key.
     * @param  string $key Table name actually, @see Oppa\Query\Result\Mysql:process()
     * @param  array  $data
     * @return array
     */
    final public function map(string $key, array $data): array
    {
        // no map model for mapping
        if (empty($data) || empty($this->map) || !isset($this->map[$key])) {
            return $data;
        }

        // let's do it!
        foreach ($this->map[$key] as $fieldName => $fieldProperties) {
            foreach ($data as &$d) {
                // keep data type
                $dType = gettype($d);
                foreach ($d as $key => $value) {
                    // match field?
                    if ($key == $fieldName) {
                        if ($dType == 'array') {
                            $d[$key] = $this->cast($value, $fieldProperties);
                        } elseif ($dType == 'object') {
                            $d->{$key} = $this->cast($value, $fieldProperties);
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Simply type-cast by data type.
     * @param  any   $value
     * @param  array $properties
     * @return any
     */
    final public function cast($value, array $properties)
    {
        // some speed?
        $nullable =& $properties['nullable'];

        // 1.000.000 iters
        // regexp-------7.442563
        // switch-------2.709796
        switch (strtolower($properties['type'])) {
            // ints
            case self::TYPE_INT:
            case self::TYPE_BIGINT:
            case self::TYPE_SMALLINT:
            case self::TYPE_MEDIUMINT:
                $value = ($nullable && $value === null) ? null : (int) $value;
                break;
            // tiny it baby.. =)
            case self::TYPE_TINYINT:
                if ($nullable && $value === null) {
                    // pass
                } else {
                    $value = (int) $value;
                    if ($this->mapOptions['tiny2bool']
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
                $value = ($nullable && $value === null) ? null : (float) $value;
                break;
        }

        return $value;
    }
}
