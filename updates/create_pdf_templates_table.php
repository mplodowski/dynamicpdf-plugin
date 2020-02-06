<?php

namespace Renatio\DynamicPDF\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use October\Rain\Support\Facades\Schema;

/**
 * Class CreateTemplatesTable
 * @package Renatio\DynamicPDF\Updates
 */
class CreateTemplatesTable extends Migration
{

    /**
     * @return void
     */
    public function up()
    {
        Schema::create('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('layout_id')->index()->nullable();
            $table->string('code')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('content_html')->nullable();
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('renatio_dynamicpdf_pdf_templates');
    }
}
