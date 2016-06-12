<?php

namespace Renatio\DynamicPDF\Tests\Models;

use Renatio\DynamicPDF\Models\Template;
use Renatio\DynamicPDF\Models\Layout;
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

    /**
     * @test
     * @expectedException \October\Rain\Database\ModelException
     */
    public function it_validate_title_is_required()
    {
        factory(Template::class)->create(['title' => '']);
    }

    /**
     * @test
     * @expectedException \October\Rain\Database\ModelException
     */
    public function it_validate_code_is_required()
    {
        factory(Template::class)->create(['code' => '']);
    }

    /**
     * @test
     * @expectedException \October\Rain\Database\ModelException
     */
    public function it_validate_code_is_unique()
    {
        factory(Template::class)->create(['code' => 'test']);
        factory(Template::class)->create(['code' => 'test']);
    }

    /**
     * @test
     * @expectedException \October\Rain\Database\ModelException
     */
    public function it_validate_content_html_is_required()
    {
        factory(Template::class)->create(['content_html' => '']);
    }

    /** @test */
    public function it_belongs_to_layout()
    {
        $template = factory(Template::class)->create();

        $template->layout()->associate(factory(Layout::class)->create());

        $this->assertEquals(2, $template->layout->id);
        $this->isInstanceOf(Layout::class, $template->layout);
    }

    /** @test */
    public function it_has_html_attribute_property()
    {
        $template = factory(Template::class)->create(['content_html' => '<p>test</p>']);

        $this->assertContains('<p>test</p>', $template->html);
    }

}