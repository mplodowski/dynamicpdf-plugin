<?php

namespace Renatio\DynamicPDF\Tests\Models;

use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Renatio\DynamicPDF\Tests\TestCase;

/**
 * Class TemplateTest
 * @package Renatio\DynamicPDF\Tests\Models
 */
class TemplateTest extends TestCase
{

    /** @test */
    public function it_creates_template()
    {
        $template = factory(Template::class)->create();

        $this->assertEquals(2, $template->id);
    }

    /** @test */
    public function it_validates_title()
    {
        $template = factory(Template::class)->make();

        $this->assertArrayHasKey('title', $template->rules);
        $this->assertEquals('required|max:100', $template->rules['title']);
    }

    /** @test */
    public function it_validates_code()
    {
        $template = factory(Template::class)->make();

        $this->assertArrayHasKey('code', $template->rules);
        $this->assertEquals('required|max:50|unique:renatio_dynamicpdf_pdf_templates', $template->rules['code']);
    }

    /** @test */
    public function it_validates_content_html()
    {
        $template = factory(Template::class)->make();

        $this->assertArrayHasKey('content_html', $template->rules);
        $this->assertEquals('required', $template->rules['content_html']);
    }

    /** @test */
    public function it_belongs_to_layout()
    {
        $template = new Template;

        $this->assertArrayHasKey('layout', $template->belongsTo);
        $this->assertContains(Layout::class, $template->belongsTo['layout']);
    }

    /** @test */
    public function it_has_html_accessor()
    {
        $template = factory(Template::class)->create(['content_html' => '<p>test</p>']);

        $this->assertContains('<p>test</p>', $template->html);
    }

    /** @test */
    public function it_finds_template_by_code()
    {
        factory(Template::class)->create(['code' => 'test']);

        $template = Template::byCode('test');

        $this->assertEquals('test', $template->code);
    }

}