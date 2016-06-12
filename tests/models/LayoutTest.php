<?php

namespace Renatio\DynamicPDF\Tests\Models;

use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Tests\TestCase;

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

    /**
     * @test
     * @expectedException \October\Rain\Database\ModelException
     */
    public function it_validate_name_is_required()
    {
        factory(Layout::class)->create(['name' => '']);
    }

    /**
     * @test
     * @expectedException \October\Rain\Database\ModelException
     */
    public function it_validate_code_is_required()
    {
        factory(Layout::class)->create(['code' => '']);
    }

    /**
     * @test
     * @expectedException \October\Rain\Database\ModelException
     */
    public function it_validate_code_is_unique()
    {
        factory(Layout::class)->create(['code' => 'test']);
        factory(Layout::class)->create(['code' => 'test']);
    }

    /**
     * @test
     * @expectedException \October\Rain\Database\ModelException
     */
    public function it_validate_content_html_is_required()
    {
        factory(Layout::class)->create(['content_html' => '']);
    }

    /** @test */
    public function it_has_html_attribute_property()
    {
        $layout = factory(Layout::class)->create(['content_html' => '<p>test</p>']);

        $this->assertContains('<p>test</p>', $layout->html);
    }

}