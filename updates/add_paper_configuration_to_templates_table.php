<?php

namespace Renatio\DynamicPDF\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddPaperConfigurationToTemplatesTable extends Migration
{
    public function up()
    {
        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->string('size')->nullable();
            $table->string('orientation')->nullable();
        });
    }

    public function down()
    {
        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->dropColumn(['size', 'orientation']);
        });
    }
}
