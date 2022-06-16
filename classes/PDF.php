<?php

namespace Renatio\DynamicPDF\Classes;

use Barryvdh\DomPDF\Facade\Pdf as PdfFacade;

class PDF extends PdfFacade
{
    protected static function getFacadeAccessor()
    {
        return 'dynamicpdf';
    }
}
