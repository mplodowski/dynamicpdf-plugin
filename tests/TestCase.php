<?php

namespace Renatio\DynamicPDF\Tests;

use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

abstract class TestCase extends OctoberPestTestCase
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createLayout(array $attributes = []): Layout
    {
        return Layout::create(array_merge([
            'name' => 'Test Layout',
            'code' => 'test.layout.' . uniqid(),
            'content_html' => '<html><body>{{ content_html }}</body></html>',
            'content_css' => '',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createTemplate(array $attributes = []): Template
    {
        return Template::create(array_merge([
            'title' => 'Test Template',
            'code' => 'test.template.' . uniqid(),
            'content_html' => '<p>Test content</p>',
            'is_custom' => true,
        ], $attributes));
    }
}
