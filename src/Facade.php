<?php

namespace GPS\Popov;

/**
 * A convenience class to use in fixture generation to 
 * wrap any calls to the factory in a closure.
 */
class Facade
{
    private static $instance;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new Factory();
        }

        return self::$instance;
    }

    public static function __callStatic($method, $args)
    {
        $factory = self::instance();

        return $factory->close($method, $args);
    }
}
