<?php

namespace Renatio\DynamicPDF\Classes;

use Cms\Classes\Controller;
use Cms\Classes\Theme;

/**
 * The core constructor takes over the request's current controller, which would detach the
 * page being rendered when a PDF is built mid-request; this one is current only while rendering.
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
