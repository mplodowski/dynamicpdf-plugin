<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

describe('create_revisions_table', function () {
    it('drops the table and creates it again', function () {
        $migration = require plugins_path('renatio/dynamicpdf/updates/20261012_0002_create_revisions_table.php');

        $migration->down();

        expect(Schema::hasTable('renatio_dynamicpdf_revisions'))->toBeFalse();

        $migration->up();

        expect(Schema::hasColumns('renatio_dynamicpdf_revisions', ['revisionable_type', 'revisionable_id', 'user_id', 'user_name', 'data', 'created_at']))->toBeTrue();
    });

    it('runs again over the table an install of the renamed 8.1.0 already has', function () {
        $migration = require plugins_path('renatio/dynamicpdf/updates/20261012_0002_create_revisions_table.php');
        DB::table('renatio_dynamicpdf_revisions')->insert(['revisionable_type' => 'x', 'revisionable_id' => 1, 'data' => '{}']);

        $migration->up();

        expect(DB::table('renatio_dynamicpdf_revisions')->count())->toBe(1);
    });
});
