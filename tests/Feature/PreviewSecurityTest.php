<?php

use Backend\Facades\Backend;

describe('PDF preview', function () {
    beforeEach(function () {
        actingAsPdfManager();
        config(['dompdf.options.enable_php' => true]);
        $this->marker = sys_get_temp_dir() . '/dynamicpdf-php-' . uniqid();
        $this->script = '<p>Body</p><script type="text/php">file_put_contents(' . var_export($this->marker, true) . ', "ran");</script>';
    });

    afterEach(fn () => @unlink($this->marker));

    it('never runs inline PHP, even when the dompdf configuration enables it', function (string $path) {
        $template = $this->createTemplate(['content_html' => $this->script]);

        if ($path === 'unsaved') {
            $this->previewUnsaved('templates', $template->id, ['content_html' => $this->script], 'pdf')->assertOk();
        } else {
            $this->get(Backend::url('renatio/dynamicpdf/templates/previewpdf/' . $template->id))->assertOk();
        }

        expect(file_exists($this->marker))->toBeFalse();
    })->with(['saved', 'unsaved']);
});

describe('Encryption', function () {
    it('keeps the passwords out of stack traces', function () {
        $wrapper = app('dynamicpdf')->setOption(['pdfBackend' => 'GD']);
        $wrapper->loadHTML('<p>x</p>');

        $trace = [];

        try {
            $wrapper->encrypt('user-secret', 'owner-secret');
        } catch (RuntimeException $e) {
            $trace = $e->getTrace();
        }

        $arguments = collect($trace)->pluck('args')->flatten()->filter(fn (mixed $value): bool => is_string($value));

        expect($trace)->not->toBe([])
            ->and($arguments->contains('user-secret'))->toBeFalse()
            ->and($arguments->contains('owner-secret'))->toBeFalse();
    });
});
