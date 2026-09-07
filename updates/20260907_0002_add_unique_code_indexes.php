<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;
use Renatio\DynamicPDF\Models\Layout;

return new class extends Migration
{
    public function up()
    {
        $this->deduplicateTemplates();
        $this->deduplicateLayouts();

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
     * Concurrent syncs could insert the same code twice. The customised row wins, then the oldest.
     */
    protected function deduplicateTemplates(): void
    {
        foreach ($this->duplicateCodes('renatio_dynamicpdf_pdf_templates') as $code) {
            $keep = DB::table('renatio_dynamicpdf_pdf_templates')->where('code', $code)->orderByDesc('is_custom')->orderBy('id')->value('id');

            DB::table('renatio_dynamicpdf_pdf_templates')->where('code', $code)->where('id', '<>', $keep)->delete();
        }
    }

    /**
     * The locked row wins, then the oldest; templates using a removed copy follow the kept one
     * and the copies are deleted through the model so their attachments go with them.
     */
    protected function deduplicateLayouts(): void
    {
        foreach ($this->duplicateCodes('renatio_dynamicpdf_pdf_layouts') as $code) {
            $keep = DB::table('renatio_dynamicpdf_pdf_layouts')->where('code', $code)->orderByDesc('is_locked')->orderBy('id')->value('id');
            $losers = DB::table('renatio_dynamicpdf_pdf_layouts')->where('code', $code)->where('id', '<>', $keep)->pluck('id');

            DB::table('renatio_dynamicpdf_pdf_templates')->whereIn('layout_id', $losers)->update(['layout_id' => $keep]);

            Layout::whereIn('id', $losers)->get()->each->delete();
        }
    }

    /**
     * @return array<int, string>
     */
    protected function duplicateCodes(string $table): array
    {
        return DB::table($table)->groupBy('code')->havingRaw('COUNT(*) > 1')->pluck('code')->all();
    }
};
