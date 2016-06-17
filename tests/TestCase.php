<?php

namespace Renatio\DynamicPDF\Tests;

use Illuminate\Database\Eloquent\Factory;
use PluginTestCase;
use Illuminate\Foundation\AliasLoader;

/**
 * Class TestCase
 * @package Renatio\DynamicPDF\Tests
 */
class TestCase extends PluginTestCase
{

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->changeDefaultFactoriesPath();

        $this->app->register('Barryvdh\DomPDF\ServiceProvider');
        $this->app->register('Renatio\DynamicPDF\Classes\ServiceProvider');

        AliasLoader::getInstance()->alias('PDF', 'Renatio\DynamicPDF\Classes\PDF');
    }

    /**
     * @return void
     */
    private function changeDefaultFactoriesPath()
    {
        $this->app->singleton('Illuminate\Database\Eloquent\Factory', function () {
            $faker = $this->app->make('Faker\Generator');

            return Factory::construct($faker, base_path('plugins/renatio/dynamicpdf/tests/factories'));
        });
    }

}