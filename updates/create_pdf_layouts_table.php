<?php

namespace Renatio\DynamicPDF\Updates;

use October\Rain\Database\Updates\Migration;
use October\Rain\Support\Facades\Schema;

/**
 * Class CreateLayoutsTable
 * @package Renatio\DynamicPDF\Updates
 */
class CreateLayoutsTable extends Migration
{

    /**
     * @return void
     */
    public function up()
    {
        Schema::create('renatio_dynamicpdf_pdf_layouts', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('content_html')->nullable();
            $table->text('content_css')->nullable();
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('renatio_dynamicpdf_pdf_layouts');
    }

}