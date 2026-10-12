<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

describe('replace_is_locked_with_is_custom_on_layouts', function () {
    beforeEach(function () {
        $this->migration = require plugins_path('renatio/dynamicpdf/updates/20261012_0001_replace_is_locked_with_is_custom_on_layouts.php');
        $this->migration->down();

        $this->flags = fn (string $column): array => DB::table('renatio_dynamicpdf_pdf_layouts')
            ->pluck($column, 'code')
            ->map(fn (mixed $value): bool => (bool) $value)
            ->all();
    });

    it('turns is_locked into the inverse is_custom and back', function () {
        foreach (['acme::locked' => true, 'acme::edited' => false] as $code => $locked) {
            DB::table('renatio_dynamicpdf_pdf_layouts')->insert(['code' => $code, 'name' => $code, 'content_html' => '<p>x</p>', 'is_locked' => $locked]);
        }

        $this->migration->up();

        expect(Schema::hasColumn('renatio_dynamicpdf_pdf_layouts', 'is_locked'))->toBeFalse()
            ->and(($this->flags)('is_custom'))->toBe(['acme::locked' => false, 'acme::edited' => true]);

        $this->migration->down();

        expect(Schema::hasColumn('renatio_dynamicpdf_pdf_layouts', 'is_custom'))->toBeFalse()
            ->and(($this->flags)('is_locked'))->toBe(['acme::locked' => true, 'acme::edited' => false]);
    });
});
