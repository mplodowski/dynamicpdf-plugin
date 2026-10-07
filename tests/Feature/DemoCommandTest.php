<?php

use Illuminate\Support\Facades\Artisan;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Renatio\DynamicPDF\Plugin;
use System\Models\Parameter;

describe('dynamicpdf:demo', function () {
    it('creates the demo templates and layouts and removes them again', function () {
        expect(Artisan::call('dynamicpdf:demo'))->toBe(0)
            ->and(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeTrue()
            ->and(Layout::whereCode('renatio.dynamicpdf::pdf.layouts.default')->exists())->toBeTrue()
            ->and(Artisan::call('dynamicpdf:demo', ['--disable' => true]))->toBe(0)
            ->and(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeFalse()
            ->and(Layout::whereCode('renatio.dynamicpdf::pdf.layouts.default')->exists())->toBeFalse();
    });

    it('keeps a demo layout that a user template still uses', function () {
        Artisan::call('dynamicpdf:demo');
        $layout = Layout::whereCode('renatio.dynamicpdf::pdf.layouts.default')->firstOrFail();
        $template = $this->createTemplate(['layout_id' => $layout->id]);

        Artisan::call('dynamicpdf:demo', ['--disable' => true]);

        expect(Layout::find($layout->id))->not->toBeNull()
            ->and($template->fresh()?->layout_id)->toBe($layout->id)
            ->and(Layout::whereKey($layout->id)->first()?->getAttribute('from_view'))->toBeFalsy()
            ->and(Artisan::output())->toContain('Kept renatio.dynamicpdf::pdf.layouts.default');
    });

    it('fails and lists the codes when a demo template could not be synchronised', function () {
        Template::creating(fn () => throw new RuntimeException('cannot write'));

        $exitCode = Artisan::call('dynamicpdf:demo');

        expect($exitCode)->toBe(1)
            ->and(Artisan::output())->toContain('renatio.dynamicpdf::pdf.invoice');
    });

    it('keeps a customised demo template and says so', function () {
        Artisan::call('dynamicpdf:demo');
        Template::whereCode('renatio.dynamicpdf::pdf.invoice')->update(['is_custom' => true, 'content_html' => '<p>Mine</p>']);

        Artisan::call('dynamicpdf:demo', ['--disable' => true]);

        expect(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->value('content_html'))->toBe('<p>Mine</p>')
            ->and(Template::whereCode('renatio.dynamicpdf::pdf.header_and_footer')->exists())->toBeFalse()
            ->and(Artisan::output())->toContain('Kept renatio.dynamicpdf::pdf.invoice');
    });

    it('still removes the demo records when the demo is already switched off', function () {
        Artisan::call('dynamicpdf:demo');
        Artisan::call('dynamicpdf:demo', ['--disable' => true]);
        Artisan::call('dynamicpdf:demo');
        Parameter::set(Plugin::DEMO_PARAMETER, 0);

        Artisan::call('dynamicpdf:demo', ['--disable' => true]);

        expect(Template::whereCode('renatio.dynamicpdf::pdf.invoice')->exists())->toBeFalse()
            ->and(Layout::whereCode('renatio.dynamicpdf::pdf.layouts.default')->exists())->toBeFalse();
    });

    it('keeps a customised demo layout and lets a kept layout follow its view again once the demo is back', function () {
        Artisan::call('dynamicpdf:demo');
        Layout::whereCode('renatio.dynamicpdf::pdf.layouts.header_and_footer')->update(['is_locked' => false]);
        $used = Layout::whereCode('renatio.dynamicpdf::pdf.layouts.default')->firstOrFail();
        $this->createTemplate(['layout_id' => $used->id]);

        Artisan::call('dynamicpdf:demo', ['--disable' => true]);
        $output = Artisan::output();
        Artisan::call('dynamicpdf:demo');

        expect(Layout::whereCode('renatio.dynamicpdf::pdf.layouts.header_and_footer')->exists())->toBeTrue()
            ->and($output)->toContain('Kept renatio.dynamicpdf::pdf.layouts.header_and_footer, it was customized.')
            ->and($output)->toContain('Kept renatio.dynamicpdf::pdf.layouts.default, templates still use it.')
            ->and(Layout::whereKey($used->id)->first()?->getAttribute('from_view'))->toBeTruthy();
    });
});
