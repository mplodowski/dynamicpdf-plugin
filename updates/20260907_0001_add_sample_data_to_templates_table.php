<?php

use Illuminate\Support\Facades\Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->text('sample_data')->nullable();
        });
    }

    public function down()
    {
        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->dropColumn('sample_data');
        });
    }
};
