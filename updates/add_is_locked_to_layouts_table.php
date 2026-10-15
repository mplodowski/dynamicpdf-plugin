<?php

namespace Renatio\DynamicPDF\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Schema;

class AddIsLockedToLayoutsTable extends Migration
{
    public function up(): void
    {
        Schema::table('renatio_dynamicpdf_pdf_layouts', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('renatio_dynamicpdf_pdf_layouts', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });
    }
}
