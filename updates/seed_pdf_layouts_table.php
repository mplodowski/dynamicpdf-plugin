<?php

namespace Renatio\DynamicPDF\Updates;

use Renatio\DynamicPDF\Models\Layout;
use Seeder;
use File;
use System\Models\File as FileModel;

/**
 * Class SeedPdfLayoutsTable
 * @package Renatio\DynamicPDF\Updates
 */
class SeedPdfLayoutsTable extends Seeder
{

    /**
     * @return void
     */
    public function run()
    {
        Layout::create([
            'name' => 'Invoice',
            'code' => 'renatio::invoice',
            'content_html' => File::get(__DIR__ . '/layouts/invoice.htm'),
            'content_css' => File::get(__DIR__ . '/layouts/style_invoice.css'),
        ]);

        $file = FileModel::where(['field' => 'background_img', 'attachment_id' => 1])->first();

        if ( ! is_object($file)) {
            FileModel::create([
                'disk_name' => '55428b6d4425d031778535.jpg',
                'file_name' => 'invoice.jpg',
                'file_size' => '47454',
                'content_type' => 'image/jpeg',
                'field' => 'background_img',
                'attachment_id' => 1,
                'attachment_type' => 'Renatio\DynamicPDF\Models\Layout',
            ]);
        }

        $storage_dir = storage_path('app/uploads/public/554/28b/6d4');
        $file = $storage_dir . '/55428b6d4425d031778535.jpg';

        if ( ! File::exists($file)) {
            File::makeDirectory($storage_dir, 0755, true);
            File::copy('plugins/renatio/dynamicpdf/assets/images/invoice.jpg', $file);
        }
    }

}