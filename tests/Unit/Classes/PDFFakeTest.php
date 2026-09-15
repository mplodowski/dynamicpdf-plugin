<?php

use PHPUnit\Framework\AssertionFailedError;
use Renatio\DynamicPDF\Classes\PDF;

describe('PDF::fake()', function () {
    it('records renders without needing the template and answers with an empty PDF response', function () {
        $fake = PDF::fake();

        $response = PDF::loadTemplate('acme::pdf.invoice', ['order' => 7], layout: 'acme::pdf.layouts.b', locale: 'de')->stream('invoice.pdf');

        expect($response->headers->get('Content-Type'))->toBe('application/pdf')
            ->and(PDF::loadTemplate('acme::pdf.invoice')->output())->toBeEmpty()
            ->and(app('dynamicpdf'))->toBe($fake);

        $fake->assertRendered('acme::pdf.invoice', fn (array $data, array $render): bool => ($data['order'] ?? null) === 7 && $render['layout'] === 'acme::pdf.layouts.b' && $render['locale'] === 'de');
        $fake->assertRenderedTimes('acme::pdf.invoice', 2);
        $fake->assertNotRendered('acme::pdf.other');
    });

    it('returns a file record that saves without touching the disk', function () {
        PDF::fake();
        $layout = $this->createLayout();

        $layout->setAttribute('background_img', PDF::loadTemplate('acme::pdf.invoice')->toFile('invoice.pdf', public: false));
        $layout->save();

        expect(\System\Models\File::where('attachment_id', $layout->id)->where('field', 'background_img')->count())->toBe(1);
    });

    it('matches the real Content-Disposition header', function () {
        PDF::fake();

        expect(PDF::loadTemplate('acme::pdf.invoice')->download('faktura-ą.pdf')->headers->get('Content-Disposition'))
            ->toBe("attachment; filename=faktura-a.pdf; filename*=utf-8''faktura-%C4%85.pdf");
    });

    it('fails the assertions the way a test expects', function () {
        $fake = PDF::fake();

        expect(fn () => $fake->assertRendered('acme::pdf.invoice'))->toThrow(AssertionFailedError::class);

        PDF::loadTemplate('acme::pdf.invoice');

        expect(fn () => $fake->assertNothingRendered())->toThrow(AssertionFailedError::class)
            ->and(fn () => $fake->assertRendered('acme::pdf.invoice', fn (array $data): bool => isset($data['missing'])))->toThrow(AssertionFailedError::class);
    });

    it('replaces a wrapper the facade has already resolved', function () {
        PDF::getFacadeRoot();

        $fake = PDF::fake();

        expect(PDF::getFacadeRoot())->toBe($fake);
    });
});
