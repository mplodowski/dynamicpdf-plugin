<?php

use Illuminate\Support\Facades\Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->mediumText('description')->nullable()->change();
            $table->mediumText('content_html')->nullable()->change();
            $table->mediumText('sample_data')->nullable()->change();
        });

        Schema::table('renatio_dynamicpdf_pdf_layouts', function (Blueprint $table) {
            $table->mediumText('content_html')->nullable()->change();
            $table->mediumText('content_css')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->text('content_html')->nullable()->change();
            $table->text('sample_data')->nullable()->change();
        });

        Schema::table('renatio_dynamicpdf_pdf_layouts', function (Blueprint $table) {
            $table->text('content_html')->nullable()->change();
            $table->text('content_css')->nullable()->change();
        });
    }
};
