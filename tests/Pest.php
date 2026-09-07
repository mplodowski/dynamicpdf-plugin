<?php

use Renatio\DynamicPDF\Tests\TestCase;

pest()->extend(TestCase::class)->beforeEach(function () {
    $this->setUpOctoberPlugin();
})->afterEach(function () {
    $this->tearDownOctoberPlugin();
})->in(__DIR__);
