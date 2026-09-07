<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up()
    {
        $this->deduplicate('renatio_dynamicpdf_pdf_templates', 'is_custom');
        $this->deduplicate('renatio_dynamicpdf_pdf_layouts', 'is_locked');

        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->unique('code');
        });

        Schema::table('renatio_dynamicpdf_pdf_layouts', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    public function down()
    {
        Schema::table('renatio_dynamicpdf_pdf_templates', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });

        Schema::table('renatio_dynamicpdf_pdf_layouts', function (Blueprint $table) {
            $table->dropUnique(['code']);
        });
    }

    /**
     * Concurrent syncs could insert the same code twice; the customised (or locked) row
     * wins, then the oldest, so nothing a user edited is lost.
     */
    protected function deduplicate(string $table, string $keepFlag): void
    {
        $duplicates = DB::table($table)->select('code')->groupBy('code')->havingRaw('COUNT(*) > 1')->pluck('code');

        foreach ($duplicates as $code) {
            $keep = DB::table($table)->where('code', $code)->orderByDesc($keepFlag)->orderBy('id')->value('id');

            DB::table($table)->where('code', $code)->where('id', '<>', $keep)->delete();
        }
    }
};
