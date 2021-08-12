<?php

namespace Renatio\DynamicPDF\Updates;

use Illuminate\Support\Facades\DB;
use October\Rain\Database\Updates\Migration;

class FixCustomTemplatesError extends Migration
{
    public function up()
    {
        DB::table('renatio_dynamicpdf_pdf_templates')
            ->update(['is_custom' => 1]);
    }

    public function down()
    {
        //
    }
}
