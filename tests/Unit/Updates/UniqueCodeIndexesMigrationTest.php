<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use October\Rain\Database\Schema\Blueprint;

describe('add_unique_code_indexes', function () {
    beforeEach(function () {
        foreach (['renatio_dynamicpdf_pdf_templates', 'renatio_dynamicpdf_pdf_layouts'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                if (Schema::hasIndex($name, ['code'], 'unique')) {
                    $table->dropUnique(['code']);
                }
            });
        }
    });

    it('keeps the customised template, the locked layout and repoints the orphaned template', function () {
        $keptLayout = DB::table('renatio_dynamicpdf_pdf_layouts')->insertGetId([
            'code' => 'acme::layout', 'name' => 'Kept', 'content_html' => '<p>kept</p>', 'is_locked' => true,
        ]);
        $losingLayout = DB::table('renatio_dynamicpdf_pdf_layouts')->insertGetId([
            'code' => 'acme::layout', 'name' => 'Losing', 'content_html' => '<p>losing</p>', 'is_locked' => false,
        ]);

        $losingTemplate = DB::table('renatio_dynamicpdf_pdf_templates')->insertGetId([
            'code' => 'acme::invoice', 'title' => 'Losing', 'content_html' => '<p>losing</p>', 'is_custom' => false,
            'layout_id' => $losingLayout,
        ]);
        $keptTemplate = DB::table('renatio_dynamicpdf_pdf_templates')->insertGetId([
            'code' => 'acme::invoice', 'title' => 'Kept', 'content_html' => '<p>kept</p>', 'is_custom' => true,
            'layout_id' => $losingLayout,
        ]);

        (require plugins_path('renatio/dynamicpdf/updates/20260907_0002_add_unique_code_indexes.php'))->up();

        expect(DB::table('renatio_dynamicpdf_pdf_templates')->where('code', 'acme::invoice')->pluck('id')->all())
            ->toBe([$keptTemplate])
            ->and(DB::table('renatio_dynamicpdf_pdf_layouts')->where('code', 'acme::layout')->pluck('id')->all())
            ->toBe([$keptLayout])
            ->and(DB::table('renatio_dynamicpdf_pdf_templates')->where('id', $keptTemplate)->value('layout_id'))
            ->toBe($keptLayout)
            ->and(DB::table('renatio_dynamicpdf_pdf_templates')->where('id', $losingTemplate)->exists())
            ->toBeFalse()
            ->and(Schema::hasIndex('renatio_dynamicpdf_pdf_templates', ['code'], 'unique'))->toBeTrue()
            ->and(Schema::hasIndex('renatio_dynamicpdf_pdf_layouts', ['code'], 'unique'))->toBeTrue();
    });
});
