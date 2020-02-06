<?php

namespace Renatio\DynamicPDF\Updates;

use October\Rain\Database\Schema\Blueprint;
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
        Schema::create('renatio_dynamicpdf_pdf_layouts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code')->index();
            $table->string('name');
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
