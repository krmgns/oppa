<?php namespace Oppa;

final class Autoload
{
    private static $instance;

    final private function __construct() {}
    final private function __clone() {}

    final public static function initialize() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    final public function register() {
        spl_autoload_register(function($objectName) {
            if ($objectName[0] != '\\') {
                $objectName = '\\'. $objectName;
            }

            $objectRoot = sprintf('/%s/', __namespace__);
            $objectFile = str_replace('\\', '/', $objectName);
            // load only self classes/interfaceses
            if (strstr($objectFile, $objectRoot) === false) {
                return;
            }

            $objectFile = sprintf('%s/%s.php', __dir__, substr($objectFile, strlen($objectRoot)));
            if (!is_file($objectFile)) {
                throw new \RuntimeException("Class file not found. file: `{$objectFile}`");
            }
            if (!is_readable($objectFile)) {
                throw new \RuntimeException("Class file is not readable. file: `{$objectFile}`");
            }

            $require = require($objectFile);

            if (strripos($objectName, 'interface') !== false) {
                if (!interface_exists($objectName, false)) {
                    throw new \RuntimeException(
                        "Interface file `{$objectFile}` has been loaded but no " .
                        "interface found such as `{$objectName}`.");
                }

                return $require;
            }

            if (!class_exists($objectName, false)) {
                throw new \RuntimeException(
                    "Class file `{$objectFile}` has been loaded but no " .
                    "class found such as `{$objectName}`.");
            }

            return $require;
        });
    }
}

// init autoload object
return Autoload::initialize();
