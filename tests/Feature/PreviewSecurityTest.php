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

    it('never runs inline PHP, even when the dompdf configuration enables it', function (string $definition, string $path) {
        $model = $definition === 'Template' ? 'templates' : 'layouts';
        $record = $definition === 'Template'
            ? $this->createTemplate(['content_html' => $this->script])
            : $this->createLayout(['content_html' => '<html><body>' . $this->script . '</body></html>']);

        if ($path === 'unsaved') {
            $this->previewUnsaved($model, $record->id, ['content_html' => $record->content_html], 'pdf')->assertOk();
        } else {
            $this->get(Backend::url("renatio/dynamicpdf/{$model}/previewpdf/{$record->id}"))->assertOk();
        }

        expect(file_exists($this->marker))->toBeFalse();
    })->with(['Template', 'Layout'])->with(['saved', 'unsaved']);
});

describe('Encryption', function () {
    it('keeps the passwords out of stack traces', function () {
        $wrapper = app('dynamicpdf')->setOption(['pdfBackend' => 'GD']);
        $wrapper->loadHTML('<p>x</p>');

        $trace = [];
        $ignoreArgs = (string) ini_get('zend.exception_ignore_args');
        ini_set('zend.exception_ignore_args', '0');

        try {
            $wrapper->encrypt('user-secret', 'owner-secret');
        } catch (RuntimeException $e) {
            $trace = $e->getTrace();
        } finally {
            ini_set('zend.exception_ignore_args', $ignoreArgs);
        }

        $arguments = collect($trace)->pluck('args')->flatten()->filter(fn (mixed $value): bool => is_string($value));

        expect(collect($trace)->filter(fn (array $frame): bool => ($frame['args'] ?? []) !== []))->not->toBeEmpty()
            ->and($arguments->contains('user-secret'))->toBeFalse()
            ->and($arguments->contains('owner-secret'))->toBeFalse();
    });
});
