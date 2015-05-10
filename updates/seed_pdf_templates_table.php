<?php namespace Renatio\DynamicPDF\Updates;

use Carbon\Carbon;
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
        PDFTemplate::insert([
            [
                'title'        => 'Invoice',
                'description'  => 'Example Invoice Template',
                'layout_id'    => '1',
                'code'         => 'renatio::invoice',
                'content_html' => File::get(__DIR__ . '/templates/invoice.htm'),
                'created_at'   => Carbon::now(),
            ],
        ]);
    }
}