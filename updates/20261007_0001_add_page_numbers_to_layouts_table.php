<?php

use Illuminate\Support\Facades\Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    protected const COLUMNS = ['page_numbers', 'page_numbers_text', 'page_numbers_size', 'page_numbers_color', 'page_numbers_font', 'page_numbers_margin'];

    public function up(): void
    {
        if (Schema::hasColumn('renatio_dynamicpdf_pdf_layouts', 'page_numbers')) {
            return;
        }

        Schema::table('renatio_dynamicpdf_pdf_layouts', function (Blueprint $table) {
            $table->string('page_numbers')->nullable();
            $table->string('page_numbers_text')->nullable();
            $table->float('page_numbers_size')->nullable();
            $table->string('page_numbers_color')->nullable();
            $table->string('page_numbers_font')->nullable();
            $table->float('page_numbers_margin')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('renatio_dynamicpdf_pdf_layouts', 'page_numbers')) {
            return;
        }

        Schema::table('renatio_dynamicpdf_pdf_layouts', function (Blueprint $table) {
            $table->dropColumn(self::COLUMNS);
        });
    }
};
