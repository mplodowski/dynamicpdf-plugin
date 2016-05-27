<?php

namespace Renatio\DynamicPDF\Classes;

use October\Rain\Support\ServiceProvider as OctoberServiceProvider;

/**
 * Class ServiceProvider
 * @package Renatio\DynamicPDF\Classes
 */
class ServiceProvider extends OctoberServiceProvider
{

    /**
     * Bind facade to the container
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('dynamicpdf', function () {
            return new PDFWrapper;
        });
    }

}