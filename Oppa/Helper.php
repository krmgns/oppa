<?php namespace Oppa;

final class Helper
{
    final public static function getArrayValue($key, array $array, $defaultValue = null) {
        return isset($array[$key]) ? $array[$key] : $defaultValue;
    }

    final public static function getArrayValueRandom(array $array, $defaultValue = null) {
        shuffle($array);
        return isset($array[0]) ? $array[0] : $defaultValue;
    }

    final public static function getArrayValueRandomAssoc($key, array $array, $defaultValue = null) {
        $array = self::getArrayValue($key, $array);
        if (!empty($array) && ($count = count($array))) {
            return $array[mt_rand(0, $count - 1)];
        }
        return $defaultValue;
    }

    final public static function underscoreToCamelcase($input) {
        return preg_replace_callback('~_([A-Za-z])~', function($m) {
            return strtoupper($m[1]);
        }, $input);
    }

    final public static function camelcaseToUnderscore($input) {
        return preg_replace_callback('~([A-Z])~', function($m) {
            return '_'. strtolower($m[1]);
        }, $input);
    }
}
