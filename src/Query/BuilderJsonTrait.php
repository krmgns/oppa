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

namespace Oppa\Query;

use Oppa\Util;

/**
 * @package Oppa
 * @object  Oppa\Query\BuilderJsonTrait
 * @author  Kerem Güneş <k-gun@mail.com>
 */
trait BuilderJsonTrait
{
    /**
     * Select json.
     * @param  string|array $field
     * @param  string       $as
     * @param  string       $type
     * @param  bool         $reset
     * @return self
     * @throws Oppa\Query\BuilderException
     */
    private function _selectJson($field, string $as, string $type, bool $reset = true): self
    {
        if (!in_array($type, ['array', 'object'])) {
            throw new BuilderException("Given JSON type '{$type}' is not implemented!");
        }

        // reset
        $reset && $this->reset();

        static $server, $serverVersion, $serverVersionMin, $fnJsonObject, $fnJsonArray, $toField, $toJson;

        // prepare static stuff
        if ($server == null) {
            if ($this->agent->isMysql()) {
                $server = 'MySQL'; $serverVersionMin = '5.7.8';
                $fnJsonObject = 'json_object'; $fnJsonArray = 'json_array';
            } elseif ($this->agent->isPgsql()) {
                $server = 'PostgreSQL'; $serverVersionMin = '9.4';
                $fnJsonObject = 'json_build_object'; $fnJsonArray = 'json_build_array';
            }

            $serverVersion = $this->link->getDatabase()->getInfo('serverVersion');
            if (version_compare($serverVersion, $serverVersionMin) == -1) {
                throw new BuilderException(sprintf('JSON not supported by %s/v%s, minimum v%s required',
                    $server, $serverVersion, $serverVersionMin));
            }

            $toField = function ($field) {
                switch ($field) {
                    case 'null':
                        return null;
                    case 'true':
                    case 'false':
                        return ($field == 'true') ? true : false;
                    default:
                        if (is_numeric($field)) {
                            $field = strpos($field, '.') === false ? intval($field) : floatval($field);
                        } elseif ($field && $field[0] == '@') {
                            $field = $this->agent->escapeIdentifier($field);
                        } else {
                            $field = $this->agent->escape($field);
                        }
                }
                return $field;
            };

            $toJson = function ($values) use (&$toJson, &$toField, $fnJsonArray, $fnJsonObject) {
                $json = [];
                foreach ($values as $key => $value) {
                    $keyType = gettype($key);
                    $valueType = gettype($value);
                    if ($valueType == 'array') {
                        // eg: 'bar' => ['baz' => ['a', ['b' => ['c:d'], ...]]]
                        $json[] = is_string($key) ? $this->agent->quote(trim($key)) .', '. $toJson($value)
                            : $toJson($value);
                    } elseif ($keyType == 'integer') {
                        // eg: ['uid: @u.id' or 'uid' => '@u.id', 'user' => ['id: @u.id' or 'id' => '@u.id', ...], ...]
                        if ($valueType == 'string' && strpbrk($value, ',:')) {
                            if (strpos($value, ',')) {
                                $json[] = $toJson(Util::split(',', $value));
                            } elseif (strpos($value, ':')) {
                                [$key, $value] = Util::split(':', $value, 2);
                                if (!isset($key, $value)) {
                                    throw new BuilderException('Field name and value must be given fo JSON objects!');
                                }

                                if (!isset($json[0])) {
                                    $json[0] = $fnJsonObject; // tick
                                }

                                $json[] = $this->agent->quote(trim($key)) .', '. $toField($value);
                            }
                        } else {
                            // eg: ['u.id', '@u.name', 1, 2, 3, true, ...]
                            if ($valueType == 'integer') {
                                $value = $toField($value);
                            }

                            if (!isset($json[0])) {
                                $json[0] = $fnJsonArray; // tick
                            }

                            $json[] = $value;
                        }
                    } elseif ($keyType == 'string') {
                        // eg: ['uid' => '@u.id']
                        if (!isset($json[0])) {
                            $json[0] = $fnJsonObject; // tick
                        }

                        $json[] = $this->agent->quote(trim($key)) .', '. $toField($value);
                    }
                }

                if ($json) {
                    $fn = array_shift($json);
                    $json = $fn ? $fn .'('. join(', ', $json) .')' : '';
                    if (substr($json, -2) == '()') { // .. :(
                        $json = substr($json, 0, -2);
                    }
                    return $json;
                }

                return null;
            };
        }

        $json = [];
        $jsonJoins = false;
        if (is_string($field)) {
            foreach (Util::split(',', $field) as $field) {
                if ($type == 'object') {
                    // eg: 'id: @id, ...'
                    [$key, $value] = Util::split(':', $field, 2);
                    if (!isset($key, $value)) {
                        throw new BuilderException('Field name and value must be given fo JSON objects!');
                    }
                    $json[] = $this->agent->quote(trim($key));
                    $json[] = $toField($value);
                } elseif ($type == 'array') {
                    // eg: 'id, ...'
                    $json[] = $toField($field);
                }
            }
        } elseif (is_array($field)) {
            $keyIndex = 0;
            foreach ($field as $key => $value) {
                $keyType = gettype($key);
                if ($type == 'object') {
                    // eg: ['id' => '@id', ... or 0 => 'id: @id, ...', ...]
                    if ($keyType == 'integer') {
                        $value = Util::split(',', $value);
                    } elseif ($keyType != 'string') {
                        throw new BuilderException("Field name must be string, {$keyType} given!");
                    }

                    if (is_array($value)) {
                        if ($keyType == 'string') {
                            // eg: ['id' => '@id', ...]
                            $key = $this->agent->quote(trim($key));
                            $json[$keyIndex][$key] = $toJson($value);
                        } else {
                            // eg: [0 => 'id: @id, ...', ...]
                            $value = $toJson($value);
                            $value = preg_replace('~json(?:_build)?_(?:object|array)\((.+)\)$~', '\1', $value); // :(
                            $json[$keyIndex][] = [$value];
                        }
                        $jsonJoins = true;
                        continue;
                    } elseif (is_string($value)) {
                        $key = $this->agent->quote(trim($key));
                        $json[$keyIndex][$key] = $toJson(Util::split(',', $value));
                        $jsonJoins = true;
                        continue;
                    }

                    $json[] = $key .', '. $toField($value);
                } elseif ($type == 'array') {
                    // eg: ['@id', '@name', ...]
                    if ($keyType != 'integer') {
                        throw new BuilderException("Field name must be int, {$keyType} given!");
                    }
                    $json[] = $toField($value);
                }
                $keyIndex++;
            }
        } else {
            throw new BuilderException(sprintf('String and array fields accepted only, %s given!',
                gettype($field)));
        }

        $as = $this->agent->quoteField($as);
        $fn = ($type == 'object') ? $fnJsonObject : $fnJsonArray;

        if ($jsonJoins) {
            $jsonJoins = [];
            foreach ($json[0] as $key => $value) {
                $jsonJoins[] = is_array($value) ? join(', ', $value) : $key .', '. $value;
            }
            $json = $jsonJoins;
        }

        return $this->push('select', sprintf('%s(%s) AS %s', $fn, join(', ', $json), $as));
    }
}
