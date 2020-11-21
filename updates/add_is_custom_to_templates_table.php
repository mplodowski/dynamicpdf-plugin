<?php

namespace Renatio\DynamicPDF\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use October\Rain\Support\Facades\Schema;

class AddIsCustomToTemplatesTable extends Migration
{

    public function up()
    {
        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->boolean('is_custom')->default(false);
        });
    }

    public function down()
    {
        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->dropColumn('is_custom');
        });
    }
}
