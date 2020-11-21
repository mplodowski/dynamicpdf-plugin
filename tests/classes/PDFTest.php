<?php

namespace Renatio\DynamicPDF\Tests\Classes;

use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Renatio\DynamicPDF\Tests\TestCase;

class PDFTest extends TestCase
{

    /** @test */
    public function it_loads_html_from_template()
    {
        $template = factory(Template::class)->create();

        $pdf = PDF::loadTemplate($template->code, $this->data());

        $this->assertPdfContains('John', $pdf);
    }

    /** @test */
    public function it_loads_html_from_layout()
    {
        $layout = factory(Layout::class)->create();

        $pdf = PDF::loadLayout($layout->code, $this->data());

        $this->assertPdfContains('John', $pdf);
    }

    /** @test */
    public function it_parses_html_from_template()
    {
        $template = factory(Template::class)->create();

        $output = PDF::parseTemplate($template, $this->data());

        $this->assertContains('John', $output);
        $this->assertContains('color: #fff', $output);
    }

    /** @test */
    public function it_parses_html_from_layout()
    {
        $layout = factory(Layout::class)->create();

        $output = PDF::parseLayout($layout, $this->data());

        $this->assertContains('John', $output);
        $this->assertContains('color: #fff', $output);
    }

    protected function data()
    {
        return ['name' => 'John'];
    }

    protected function assertPdfContains($search, $pdf)
    {
        $this->assertContains($search, $pdf->getDomPDF()->outputHtml());
    }
}
