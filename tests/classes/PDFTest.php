<?php

namespace Renatio\DynamicPDF\Tests\Classes;

use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Tests\TestCase;

/**
 * Class PDFTest
 * @package Renatio\DynamicPDF\Tests\Classes
 */
class PDFTest extends TestCase
{

    /** @test */
    public function it_parses_html_from_template()
    {
        $template = factory('Renatio\DynamicPDF\Models\Template')->create(['content_html' => '{{ field }}']);

        $output = PDF::parseTemplate($template, ['field' => 'test']);

        $this->assertContains('test', $output);
    }

    /** @test */
    public function it_parses_html_from_layout()
    {
        $layout = factory('Renatio\DynamicPDF\Models\Layout')->create(['content_html' => '{{ field }}']);

        $output = PDF::parseLayout($layout, ['field' => 'test']);

        $this->assertContains('test', $output);
    }

}