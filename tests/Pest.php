<?php

use Renatio\DynamicPDF\Tests\TestCase;

pest()->extend(TestCase::class)
    ->beforeEach(fn () => $this->setUpOctoberPlugin())
    ->afterEach(fn () => $this->tearDownOctoberPlugin())
    ->in(__DIR__);
