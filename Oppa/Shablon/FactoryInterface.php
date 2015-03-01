<?php namespace Oppa\Shablon;

/**
 * @package    Oppa
 * @subpackage Oppa\Shablon
 * @object     Oppa\Shablon\FactoryInterface
 * @version    v1.0
 * @author     Kerem Gunes <qeremy@gmail>
 */
interface FactoryInterface
{
    public static function build($className, array $arguments = null);
}
