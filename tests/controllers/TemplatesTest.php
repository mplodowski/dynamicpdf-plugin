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

        $this->visit('backend/renatio/dynamicpdf/templates')
            ->see($template->name)
            ->assertResponseOk();
    }

    /** @test */
    public function it_displays_create_template_page()
    {
        $this->visit('backend/renatio/dynamicpdf/templates/create')
            ->assertResponseOk();
    }

    /** @test */
    public function it_displays_update_template_page()
    {
        $template = factory(Template::class)->create();

        $this->visit('backend/renatio/dynamicpdf/templates/update/' . $template->id)
            ->assertResponseOk();
    }

    /** @test */
    public function it_displays_preview_pdf_template_page()
    {
        $template = factory(Template::class)->create();

        $this->visit('backend/renatio/dynamicpdf/templates/previewpdf/' . $template->id)
            ->seeHeader('content-type', 'application/pdf')
            ->assertResponseOk();
    }

    /** @test */
    public function it_displays_preview_html_template_page()
    {
        $template = factory(Template::class)->create();

        $this->visit('backend/renatio/dynamicpdf/templates/preview/' . $template->id)
            ->assertResponseOk();
    }

}