<?php

namespace Renatio\DynamicPDF\Classes;

use Barryvdh\DomPDF\ServiceProvider as LaravelPdfServiceProvider;
use File;
use October\Rain\Support\ServiceProvider as OctoberServiceProvider;

/**
 * Class ServiceProvider
 * @package Renatio\DynamicPDF\Classes
 */
class ServiceProvider extends OctoberServiceProvider
{

    /**
     * @return void
     */
    public function register()
    {
        $this->app->register(LaravelPdfServiceProvider::class);

        $this->bindPdfFacade();

        $this->createFontDirectory();
    }

    /**
     * @return void
     */
    protected function createFontDirectory()
    {
        $fontDir = config('dompdf.defines')['DOMPDF_FONT_CACHE'];

        if ( ! File::exists($fontDir)) {
            File::makeDirectory($fontDir);
        }
    }

    /**
     * @return void
     */
    protected function bindPdfFacade()
    {
        $this->app->bind('dynamicpdf', function ($app) {
            return new PDFWrapper($app['dompdf'], $app['config'], $app['files'], $app['view']);
        });
    }

}