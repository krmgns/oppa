<?php namespace Oppa\Shablon;

interface FactoryInterface
{
    public static function build($className, array $arguments = null);
}
