<?php

namespace Renatio\DynamicPDF\Updates;

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use October\Rain\Support\Facades\Schema;

/**
 * Class AddIsLockedToLayoutsTable
 * @package Renatio\DynamicPDF\Updates
 */
class AddIsLockedToLayoutsTable extends Migration
{

    /**
     * @return void
     */
    public function up()
    {
        Schema::table('renatio_dynamicpdf_pdf_layouts', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false);
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('renatio_dynamicpdf_pdf_layouts', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });
    }
}
