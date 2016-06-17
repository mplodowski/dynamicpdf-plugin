<?php

namespace Renatio\DynamicPDF\Tests\Models;

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
        $template = factory('Renatio\DynamicPDF\Models\Template')->create();

        $this->assertEquals(2, $template->id);
    }

    /**
     * @test
     * @expectedException \October\Rain\Database\ModelException
     */
    public function it_validate_title_is_required()
    {
        factory('Renatio\DynamicPDF\Models\Template')->create(['title' => '']);
    }

    /**
     * @test
     * @expectedException \October\Rain\Database\ModelException
     */
    public function it_validate_code_is_required()
    {
        factory('Renatio\DynamicPDF\Models\Template')->create(['code' => '']);
    }

    /**
     * @test
     * @expectedException \October\Rain\Database\ModelException
     */
    public function it_validate_code_is_unique()
    {
        factory('Renatio\DynamicPDF\Models\Template')->create(['code' => 'test']);
        factory('Renatio\DynamicPDF\Models\Template')->create(['code' => 'test']);
    }

    /**
     * @test
     * @expectedException \October\Rain\Database\ModelException
     */
    public function it_validate_content_html_is_required()
    {
        factory('Renatio\DynamicPDF\Models\Template')->create(['content_html' => '']);
    }

    /** @test */
    public function it_belongs_to_layout()
    {
        $template = factory('Renatio\DynamicPDF\Models\Template')->create();

        $template->layout()->associate(factory('Renatio\DynamicPDF\Models\Layout')->create());

        $this->assertEquals(2, $template->layout->id);
        $this->isInstanceOf('Renatio\DynamicPDF\Models\Layout', $template->layout);
    }

    /** @test */
    public function it_has_html_attribute_property()
    {
        $template = factory('Renatio\DynamicPDF\Models\Template')->create(['content_html' => '<p>test</p>']);

        $this->assertContains('<p>test</p>', $template->html);
    }

}