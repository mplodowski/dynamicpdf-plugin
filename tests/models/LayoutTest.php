<?php

namespace Renatio\DynamicPDF\Tests\Models;

use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Tests\TestCase;
use System\Models\File;

/**
 * Class LayoutTest
 * @package Renatio\DynamicPDF\Tests\Models
 */
class LayoutTest extends TestCase
{

    /** @test */
    public function it_creates_layout()
    {
        $layout = factory(Layout::class)->create();

        $this->assertEquals(2, $layout->id);
    }

    /** @test */
    public function it_validates_name()
    {
        $layout = factory(Layout::class)->make();

        $this->assertArrayHasKey('name', $layout->rules);
        $this->assertEquals('required|max:100', $layout->rules['name']);
    }

    /** @test */
    public function it_validates_code()
    {
        $layout = factory(Layout::class)->make();

        $this->assertArrayHasKey('code', $layout->rules);
        $this->assertEquals('required|max:50|unique:renatio_dynamicpdf_pdf_layouts', $layout->rules['code']);
    }

    /** @test */
    public function it_validates_content_html()
    {
        $layout = factory(Layout::class)->make();

        $this->assertArrayHasKey('content_html', $layout->rules);
        $this->assertEquals('required', $layout->rules['content_html']);
    }

    /** @test */
    public function it_attaches_one_background_img()
    {
        $layout = new Layout;

        $this->assertArrayHasKey('background_img', $layout->attachOne);
        $this->assertContains(File::class, $layout->attachOne['background_img']);
    }

    /** @test */
    public function it_has_html_accessor()
    {
        $layout = factory(Layout::class)->create(['content_html' => '<p>test</p>']);

        $this->assertContains('<p>test</p>', $layout->html);
    }

    /** @test */
    public function it_finds_layout_by_code()
    {
        factory(Layout::class)->create(['code' => 'test']);

        $layout = Layout::byCode('test');

        $this->assertEquals('test', $layout->code);
    }

}