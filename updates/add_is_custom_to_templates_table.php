<?php

namespace Renatio\DynamicPDF\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddIsCustomToTemplatesTable extends Migration
{
    public function up(): void
    {
        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->boolean('is_custom')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->dropColumn('is_custom');
        });
    }
}
