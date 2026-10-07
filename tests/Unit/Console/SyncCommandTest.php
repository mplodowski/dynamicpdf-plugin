<?php

use Illuminate\Support\Facades\Artisan;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Models\Template;

describe('dynamicpdf:sync', function () {
    it('creates registered views and reports what it did', function () {
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default', 'renatio.dynamicpdf::pdf.layouts.missing']);
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);

        $exitCode = Artisan::call('dynamicpdf:sync');
        $output = Artisan::output();

        expect(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeTrue()
            ->and($output)->toContain('renatio.dynamicpdf::pdf.layouts.default')
            ->and($output)->toContain('renatio.dynamicpdf::pdf.invoice')
            ->and($output)->toContain('renatio.dynamicpdf::pdf.layouts.missing')
            ->and($exitCode)->toBe(1);
    });

    it('fails for a stored view-driven record whose view file went missing', function (string $kind) {
        if ($kind === 'template') {
            PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.gone']);
            $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.gone', 'is_custom' => false]);
        } else {
            PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.gone']);
            $this->createLayout(['code' => 'renatio.dynamicpdf::pdf.gone', 'is_locked' => true]);
        }

        $exitCode = Artisan::call('dynamicpdf:sync');

        expect($exitCode)->toBe(1)
            ->and(Artisan::output())->toContain('Failed')->toContain('renatio.dynamicpdf::pdf.gone');
    })->with(['template', 'layout']);

    it('lists orphaned templates and deletes them with --prune', function () {
        $this->createTemplate(['code' => 'gone::pdf.stale', 'is_custom' => false]);

        Artisan::call('dynamicpdf:sync');
        $listed = Artisan::output();
        $kept = Template::whereCode('gone::pdf.stale')->exists();
        Artisan::call('dynamicpdf:sync', ['--prune' => true, '--no-interaction' => true]);
        $keptWithoutConfirmation = Template::whereCode('gone::pdf.stale')->exists();
        Artisan::call('dynamicpdf:sync', ['--prune' => true, '--force' => true]);

        expect($kept)->toBeTrue()
            ->and($keptWithoutConfirmation)->toBeTrue()
            ->and($listed)->toContain('gone::pdf.stale')->toContain('--prune')
            ->and(Template::whereCode('gone::pdf.stale')->exists())->toBeFalse();
    });
});
