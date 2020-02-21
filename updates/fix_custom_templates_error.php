<?php

namespace Renatio\DynamicPDF\Updates;

use Illuminate\Support\Facades\DB;
use October\Rain\Database\Updates\Migration;

/**
 * Class FixCustomTemplatesError
 * @package Renatio\DynamicPDF\Updates
 */
class FixCustomTemplatesError extends Migration
{

    /**
     * @return void
     */
    public function up()
    {
        DB::table('renatio_dynamicpdf_pdf_templates')
            ->update(['is_custom' => 1]);
    }

    /**
     * @return void
     */
    public function down()
    {
        //
    }
}
