<?php

describe('Self-signed certificates', function () {
    it('ships with verification enabled', function () {
        expect(config('renatio.dynamicpdf.allow_self_signed_certificates'))->toBeFalse();
    });

    it('leaves the dompdf HTTP context untouched by default', function () {
        config(['renatio.dynamicpdf.allow_self_signed_certificates' => false]);
        $template = $this->createTemplate();

        $wrapper = app('dynamicpdf')->loadTemplate($template->code);

        expect(stream_context_get_options($wrapper->getDomPDF()->getHttpContext()))->not->toHaveKey('ssl');
    });

    it('accepts self-signed certificates for loadHTML when the config enables it', function () {
        config(['renatio.dynamicpdf.allow_self_signed_certificates' => true]);

        $wrapper = app('dynamicpdf');
        $wrapper->loadHTML('<p>Hello</p>');

        expect(stream_context_get_options($wrapper->getDomPDF()->getHttpContext())['ssl']['verify_peer'])->toBeFalse();
    });

    it('keeps the policy after setOptions replaces the dompdf options', function () {
        config(['renatio.dynamicpdf.allow_self_signed_certificates' => true]);

        $wrapper = app('dynamicpdf')->setOptions(['isRemoteEnabled' => true]);
        $options = stream_context_get_options($wrapper->getDomPDF()->getHttpContext());

        expect($options['ssl']['verify_peer'])->toBeFalse()
            ->and($options['http']['follow_location'])->toBeFalse();
    });

    it('is chainable when called explicitly', function () {
        $wrapper = app('dynamicpdf');

        expect($wrapper->allowSelfSignedCertificates())->toBe($wrapper)
            ->and(stream_context_get_options($wrapper->getDomPDF()->getHttpContext())['ssl']['allow_self_signed'])->toBeTrue();
    });
});
