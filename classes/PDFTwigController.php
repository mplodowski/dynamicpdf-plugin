<?php

namespace Renatio\DynamicPDF\Classes;

use Cms\Classes\Controller;
use Cms\Classes\Theme;

/**
 * The core constructor registers itself as the controller of the request, which would
 * leave the page being rendered without its controller once a PDF is built mid-request.
 * This one is the current controller only while a PDF renders, because core helpers
 * (media tags in |content, page URLs) need one outside a front-end request too.
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

    /**
     * @param  callable(): string  $render
     */
    public function whileCurrent(callable $render): string
    {
        $previous = static::$instance;
        static::$instance = $this;

        try {
            return $render();
        } finally {
            static::$instance = $previous;
        }
    }
}
