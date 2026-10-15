<?php

namespace Renatio\DynamicPDF\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddPaperConfigurationToTemplatesTable extends Migration
{
    public function up(): void
    {
        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->string('size')->nullable();
            $table->string('orientation')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->dropColumn(['size', 'orientation']);
        });
    }
}
