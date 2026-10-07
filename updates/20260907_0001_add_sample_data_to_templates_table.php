<?php

use Illuminate\Support\Facades\Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    /**
     * Guarded because the script was on master under the untagged 8.0.4 and 8.0.5 before
     * moving to 8.1.0, so an install that ran it there runs it again.
     */
    public function up(): void
    {
        if (Schema::hasColumn('renatio_dynamicpdf_pdf_templates', 'sample_data')) {
            return;
        }

        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->text('sample_data')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('renatio_dynamicpdf_pdf_templates', 'sample_data')) {
            return;
        }

        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->dropColumn('sample_data');
        });
    }
};
