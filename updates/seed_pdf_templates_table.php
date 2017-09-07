<?php

namespace Renatio\DynamicPDF\Updates;

use Illuminate\Support\Facades\File;
use October\Rain\Database\Updates\Seeder;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

/**
 * Class SeedPdfTemplatesTable
 * @package Renatio\DynamicPDF\Updates
 */
class SeedPdfTemplatesTable extends Seeder
{

    /**
     * @return void
     */
    public function run()
    {
        $layout = Layout::find(1);

        Template::create([
            'title' => 'Invoice',
            'description' => 'Example Invoice Template',
            'layout' => $layout,
            'code' => 'renatio::invoice',
            'content_html' => File::get(__DIR__ . '/templates/invoice.htm'),
        ]);
    }

}