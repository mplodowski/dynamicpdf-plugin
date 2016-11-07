<?php

namespace Renatio\DynamicPDF\Updates;

use October\Rain\Database\Updates\Migration;
use DbDongle;

class UpdateTimestampsNullable extends Migration
{
    public function up()
    {
        DbDongle::disableStrictMode();

        DbDongle::convertTimestamps('renatio_dynamicpdf_pdf_layouts');
        DbDongle::convertTimestamps('renatio_dynamicpdf_pdf_templates');
    }

    public function down()
    {
        // ...
    }
}
