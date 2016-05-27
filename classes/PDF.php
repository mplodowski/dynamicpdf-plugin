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

}