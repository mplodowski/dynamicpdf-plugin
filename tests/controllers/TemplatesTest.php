<?php

namespace Renatio\DynamicPDF\Tests\Controllers;

use Renatio\DynamicPDF\Models\Template;

/**
 * Class TemplatesTest
 * @package Renatio\DynamicPDF\Tests\Controllers
 */
class TemplatesTest extends ControllerTestCase
{

    /** @test */
    public function it_displays_index_template_page()
    {
        $template = factory(Template::class)->create();

        $this->get('backend/renatio/dynamicpdf/templates')
            ->assertSee($template->title)
            ->assertSuccessful();
    }

    /** @test */
    public function it_displays_create_template_page()
    {
        $this->get('backend/renatio/dynamicpdf/templates/create')
            ->assertSuccessful();
    }

    /** @test */
    public function it_displays_update_template_page()
    {
        $template = factory(Template::class)->create();

        $this->get('backend/renatio/dynamicpdf/templates/update/' . $template->id)
            ->assertSuccessful();
    }

    /** @test */
    public function it_displays_preview_pdf_template_page()
    {
        $template = factory(Template::class)->create();

        $this->get('backend/renatio/dynamicpdf/templates/previewpdf/' . $template->id)
            ->assertHeader('content-type', 'application/pdf')
            ->assertSuccessful();
    }

    /** @test */
    public function it_displays_preview_html_template_page()
    {
        $template = factory(Template::class)->create();

        $this->get('backend/renatio/dynamicpdf/templates/preview/' . $template->id)
            ->assertSuccessful();
    }

}