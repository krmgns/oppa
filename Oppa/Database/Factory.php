<?php namespace Oppa\Database;

use \Oppa\Configuration;

final class Factory
{
    final static public function build(Configuration $configuration) {
        return \Oppa\Factory::build('\Oppa\Database', [$configuration]);
    }
}
