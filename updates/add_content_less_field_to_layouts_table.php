<?php namespace Renatio\DynamicPDF\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class AddContentLessFieldToLayoutsTable extends Migration
{
    public function up()
    {
        Schema::table('renatio_dynamicpdf_pdf_layouts', function($table)
        {
            $table->text('content_less')->nullable();
        });
    }
    public function down()
    {
        Schema::table('renatio_dynamicpdf_pdf_layouts', function($table)
        {
            $table->dropColumn(['content_less']);
        });
    }
}
