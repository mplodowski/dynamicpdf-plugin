<?php namespace Renatio\DynamicPDF\Updates;

use Renatio\DynamicPDF\Models\PDFLayout;
use Renatio\DynamicPDF\Models\PDFTemplate;
use Seeder;
use File;

class SeedPdfTemplatesTable extends Seeder
{

    /**
     * Sample pdf templates
     */
    public function run()
    {
        $layout = PDFLayout::find(1);

        PDFTemplate::create([
            'title'        => 'Invoice',
            'description'  => 'Example Invoice Template',
            'layout'       => $layout,
            'code'         => 'renatio::invoice',
            'content_html' => File::get(__DIR__ . '/templates/invoice.htm'),
        ]);
    }
}