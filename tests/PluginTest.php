<?php

namespace Renatio\DynamicPDF\Tests;

use App;
use PluginTestCase;
use Renatio\DynamicPDF\Plugin;

/**
 * Class PluginTest
 * @package Renatio\DynamicPDF\Tests
 */
class PluginTest extends PluginTestCase
{

    /**
     * @var
     */
    protected $plugin;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->plugin = new Plugin(new App);
    }

    /** @test */
    public function it_provides_plugin_details()
    {
        $this->assertArrayHasKey('name', $this->plugin->pluginDetails());
        $this->assertArrayHasKey('description', $this->plugin->pluginDetails());
    }

    /** @test */
    public function it_registers_navigation()
    {
        $this->assertArrayHasKey('dynamicpdf', $this->plugin->registerNavigation());
    }

    /** @test */
    public function it_registers_side_menu_navigation()
    {
        $sideMenu = $this->plugin->registerNavigation()['dynamicpdf']['sideMenu'];

        $this->assertArrayHasKey('templates', $sideMenu);
        $this->assertArrayHasKey('layouts', $sideMenu);
    }

    /** @test */
    public function it_registers_permissions()
    {
        $this->assertArrayHasKey('renatio.dynamicpdf.manage_templates', $this->plugin->registerPermissions());
        $this->assertArrayHasKey('renatio.dynamicpdf.manage_layouts', $this->plugin->registerPermissions());
    }

}