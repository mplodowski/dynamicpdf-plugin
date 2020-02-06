<?php

namespace Renatio\DynamicPDF\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use October\Rain\Support\Facades\Schema;

/**
 * Class AddPaperConfigurationToTemplatesTable
 * @package Renatio\DynamicPDF\Updates
 */
class AddPaperConfigurationToTemplatesTable extends Migration
{

    /**
     * @return void
     */
    public function up()
    {
        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->string('size')->nullable();
            $table->string('orientation')->nullable();
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->dropColumn(['size', 'orientation']);
        });
    }
}
