<?php namespace Renatio\DynamicPDF\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreatePDFTemplatesTable extends Migration
{

    public function up()
    {
        Schema::create('renatio_dynamicpdf_pdf_templates', function ($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('layout_id')->unsigned()->index()->nullable();
            $table->string('code')->unique();
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
