<?php

use PHPUnit\Framework\AssertionFailedError;
use Renatio\DynamicPDF\Classes\PDF;

describe('PDF::fake()', function () {
    afterEach(fn () => app()->forgetInstance('dynamicpdf'));

    it('records renders without needing the template and answers with an empty PDF response', function () {
        $fake = PDF::fake();

        $response = PDF::loadTemplate('acme::pdf.invoice', ['order' => 7], layout: 'acme::pdf.layouts.b', locale: 'de')->stream('invoice.pdf');

        expect($response->headers->get('Content-Type'))->toBe('application/pdf')
            ->and(PDF::loadTemplate('acme::pdf.invoice')->output())->toBeEmpty()
            ->and(app('dynamicpdf'))->toBe($fake);

        $fake->assertRendered('acme::pdf.invoice', fn (array $data, array $render): bool => ($data['order'] ?? null) === 7 && $render['layout'] === 'acme::pdf.layouts.b' && $render['locale'] === 'de');
        $fake->assertRenderedCount(2);
        $fake->assertNotRendered('acme::pdf.other');
    });

    it('fails the assertions the way a test expects', function () {
        $fake = PDF::fake();

        expect(fn () => $fake->assertRendered('acme::pdf.invoice'))->toThrow(AssertionFailedError::class);

        PDF::loadTemplate('acme::pdf.invoice');

        expect(fn () => $fake->assertNothingRendered())->toThrow(AssertionFailedError::class)
            ->and(fn () => $fake->assertRendered('acme::pdf.invoice', fn (array $data): bool => isset($data['missing'])))->toThrow(AssertionFailedError::class);
    });
});
