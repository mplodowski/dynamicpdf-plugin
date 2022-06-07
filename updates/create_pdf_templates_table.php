<?php

namespace Renatio\DynamicPDF\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class CreateTemplatesTable extends Migration
{
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

    public function down()
    {
        Schema::dropIfExists('renatio_dynamicpdf_pdf_templates');
    }
}
