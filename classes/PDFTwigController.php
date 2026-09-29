<?php

namespace Renatio\DynamicPDF\Classes;

use Cms\Classes\Controller;
use Cms\Classes\Theme;

/**
 * The core constructor registers itself as the controller of the request, which would
 * leave the page being rendered without its controller once a PDF is built mid-request.
 */
class PDFTwigController extends Controller
{
    /**
     * @param  Theme|null  $theme
     */
    public function __construct($theme = null)
    {
        $current = static::$instance;

        parent::__construct($theme);

        static::$instance = $current;
    }
}
