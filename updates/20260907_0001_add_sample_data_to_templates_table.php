<?php

use Illuminate\Support\Facades\Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    /**
     * Guarded because the script shipped briefly under 8.0.4 before moving to 8.0.5, so an
     * install that ran it there runs it again.
     */
    public function up()
    {
        if (Schema::hasColumn('renatio_dynamicpdf_pdf_templates', 'sample_data')) {
            return;
        }

        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->text('sample_data')->nullable();
        });
    }

    public function down()
    {
        if (! Schema::hasColumn('renatio_dynamicpdf_pdf_templates', 'sample_data')) {
            return;
        }

        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->dropColumn('sample_data');
        });
    }
};
