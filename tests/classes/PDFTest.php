<?php

namespace Renatio\DynamicPDF\Tests\Classes;

use Dompdf\Dompdf;
use ReflectionProperty;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Renatio\DynamicPDF\Tests\TestCase;

/**
 * Class PDFTest
 * @package Renatio\DynamicPDF\Tests\Classes
 */
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

    /** @test */
    public function it_loads_html_from_string()
    {
        $pdf = PDF::loadHTML('<p>Test</p>');

        $this->assertPdfContains('<p>Test</p>', $pdf);
    }

    /** @test */
    public function it_loads_html_from_file()
    {
        $pdf = PDF::loadFile(__DIR__ . '/../fixtures/template.htm');

        $this->assertPdfContains('{{ name }}', $pdf);
    }

    /** @test */
    public function it_returns_instance_of_dompdf()
    {
        $domPDF = PDF::getDomPDF();

        $this->assertInstanceOf(Dompdf::class, $domPDF);
    }

    /** @test */
    public function it_sets_paper_and_orientation()
    {
        $pdf = PDF::setPaper('a3', 'landscape');

        $this->assertPdfPropertyEquals('a3', $pdf, 'paper');
        $this->assertPdfPropertyEquals('landscape', $pdf, 'orientation');
    }

    /** @test */
    public function it_sets_warnings()
    {
        $pdf = PDF::setWarnings(true);

        $this->assertPdfPropertyEquals(true, $pdf, 'showWarnings');
    }

    /** @test */
    public function it_outputs_pdf_as_binary_string()
    {
        $pdf = PDF::loadHTML('<p>Test</p>');

        $this->assertNotNull($pdf->output());
    }

    /** @test */
    public function it_saves_pdf_to_file()
    {
        $filePath = __DIR__ . '/../fixtures/test.pdf';

        PDF::loadHTML('<p>Test</p>')->save($filePath);

        $this->assertTrue(file_exists($filePath));

        unlink($filePath);
    }

    /** @test */
    public function it_downloads_pdf()
    {
        $response = PDF::loadHTML('<p>Test</p>')->download();

        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
        $this->assertEquals('attachment; filename="document.pdf"', $response->headers->get('content-disposition'));
    }

    /** @test */
    public function it_streams_pdf()
    {
        $response = PDF::loadHTML('<p>Test</p>')->stream();

        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    /**
     * @return array
     */
    protected function data()
    {
        return ['name' => 'John'];
    }

    /**
     * @param $search
     * @param $pdf
     */
    protected function assertPdfContains($search, $pdf)
    {
        $this->assertContains($search, $pdf->getDomPDF()->outputHtml());
    }

    /**
     * @param $expected
     * @param $pdf
     * @param $property
     */
    protected function assertPdfPropertyEquals($expected, $pdf, $property)
    {
        $value = $this->getPrivate($pdf, $property);

        $this->assertEquals($expected, $value);
    }

    /**
     * @param $object
     * @param $property
     * @return mixed
     */
    protected function getPrivate($object, $property)
    {
        $reflector = new ReflectionProperty(get_class($object), $property);
        $reflector->setAccessible(true);

        return $reflector->getValue($object);
    }

}