<?php

namespace Renatio\DynamicPDF\Updates;

use October\Rain\Database\Updates\Migration;
use October\Rain\Support\Facades\DbDongle;

/**
 * Class UpdateTimestampsNullable
 * @package Renatio\DynamicPDF\Updates
 */
class UpdateTimestampsNullable extends Migration
{

    /**
     * @return void
     */
    public function up()
    {
        DbDongle::disableStrictMode();

        DbDongle::convertTimestamps('renatio_dynamicpdf_pdf_layouts');
        DbDongle::convertTimestamps('renatio_dynamicpdf_pdf_templates');
    }

    /**
     * @return void
     */
    public function down()
    {
        //
    }

}