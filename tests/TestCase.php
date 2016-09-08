<?php

namespace Renatio\DynamicPDF\Tests;

use Faker\Generator;
use Illuminate\Database\Eloquent\Factory;
use PluginTestCase;
use Renatio\DynamicPDF\Classes\ServiceProvider;

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

        $this->app->register(ServiceProvider::class);
    }

    /**
     * @return void
     */
    protected function changeDefaultFactoriesPath()
    {
        $this->app->singleton(Factory::class, function () {
            $faker = $this->app->make(Generator::class);

            return Factory::construct($faker, base_path('plugins/renatio/dynamicpdf/tests/factories'));
        });
    }

}