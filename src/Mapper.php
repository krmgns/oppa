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
        DATA_TYPE_INT        = 'int',
        DATA_TYPE_BIGINT     = 'bigint',
        DATA_TYPE_TINYINT    = 'tinyint',
        DATA_TYPE_SMALLINT   = 'smallint',
        DATA_TYPE_MEDIUMINT  = 'mediumint',
        DATA_TYPE_INTEGER    = 'integer',
        DATA_TYPE_SERIAL     = 'serial',
        // float
        DATA_TYPE_FLOAT      = 'float',
        DATA_TYPE_DOUBLE     = 'double',
        DATA_TYPE_DECIMAL    = 'decimal',
        DATA_TYPE_REAL       = 'real',
        DATA_TYPE_NUMERIC    = 'numeric',
        // boolean
        DATA_TYPE_BOOLEAN    = 'boolean',
        // bit
        DATA_TYPE_BIT        = 'bit';

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
        'bool' => false, // converts bits & tinyints to booleans
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
            foreach ($data as &$dat) {
                // keep data type
                $datType = gettype($dat);
                foreach ($dat as $key => $value) {
                    // match field?
                    if ($key == $fieldName) {
                        if ($datType == 'array') {
                            $dat[$key] = $this->cast($value, $fieldProperties);
                        } elseif ($datType == 'object') {
                            $dat->{$key} = $this->cast($value, $fieldProperties);
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
        // nullable?
        if ($properties['nullable'] && $value === null) {
            return $value;
        }

        // 1.000.000 iters
        // regexp-------7.442563
        // switch-------2.709796
        switch ($type = strtolower($properties['type'])) {
            case self::DATA_TYPE_INT:
            case self::DATA_TYPE_BIGINT:
            case self::DATA_TYPE_SMALLINT:
            case self::DATA_TYPE_MEDIUMINT:
            case self::DATA_TYPE_INTEGER:
            case self::DATA_TYPE_SERIAL:
                $value = (int) $value;
                break;
            case self::DATA_TYPE_FLOAT:
            case self::DATA_TYPE_DOUBLE:
            case self::DATA_TYPE_DECIMAL:
            case self::DATA_TYPE_REAL:
                $value = (float) $value;
                break;
            case self::DATA_TYPE_BOOLEAN:
                $value = ($value == 't');
                break;
            default:
                if ($this->mapOptions['bool'] && $properties['length'] === 1) {
                    if (self::DATA_TYPE_TINYINT) {
                        $value = (int) $value;
                        if ($value === 0 || $value === 1) { $value = (bool) $value; }
                    } elseif ($type == self::DATA_TYPE_BIT) {
                        $value = (string) $value;
                        // @important
                        if ($value === '0' || $value === '1') { $value = (bool) $value; }
                    }
                }
        }

        return $value;
    }
}
