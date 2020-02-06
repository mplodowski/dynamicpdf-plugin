<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Support\Facades\Facade;

/**
 * Class PDF
 * @package Renatio\DynamicPDF\Classes
 */
class PDF extends Facade
{

    /**
     * Get the registered name of the component
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'dynamicpdf';
    }

    /**
     * Resolve a new instance
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::$app->make(static::getFacadeAccessor());

        switch (count($args)) {
            case 0:
                return $instance->$method();
            case 1:
                return $instance->$method($args[0]);
            case 2:
                return $instance->$method($args[0], $args[1]);
            case 3:
                return $instance->$method($args[0], $args[1], $args[2]);
            case 4:
                return $instance->$method($args[0], $args[1], $args[2], $args[3]);
            default:
                return call_user_func_array(array($instance, $method), $args);
        }
    }
}
