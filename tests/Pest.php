<?php

require_once __DIR__ . '/TestCase.php';

uses(Renatio\DynamicPDF\Tests\TestCase::class)
    ->beforeEach(function () {
        $this->setUpOctoberPlugin();
    })
    ->afterEach(function () {
        $this->tearDownOctoberPlugin();
    })
    ->in(__DIR__);
