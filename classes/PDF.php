<?php

namespace Renatio\DynamicPDF\Classes;

use Barryvdh\DomPDF\Facade;

class PDF extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'dynamicpdf';
    }
}
