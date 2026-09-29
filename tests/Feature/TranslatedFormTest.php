<?php

use October\Rain\Support\Facades\Site;
use Renatio\DynamicPDF\Classes\SyncTemplates;

describe('Translated template form', function () {
    it('keeps a view-driven template following its view when a translation is saved', function () {
        $site = $this->enableTranslation('de');
        $directory = $this->registerViewTemplates('acme', ['invoice' => "title = \"Invoice\"\n==\n<p>English invoice</p>"]);

        (new SyncTemplates)->handle();

        $template = $this->findTemplate('acme::pdf.invoice');

        actingAsPdfManager();

        Site::withContext($site->id, fn () => $this->saveTemplateForm($template->id, [
            'title' => 'Rechnung',
            'code' => 'acme::pdf.invoice',
            'content_html' => '<p>Deutsche Rechnung</p>',
            'description' => '',
            'layout' => '',
            'size' => '',
            'orientation' => '',
        ]))->assertOk();

        $stored = $this->findTemplate('acme::pdf.invoice');

        expect($stored->is_custom)->toBeFalse()
            ->and($stored->getTranslation('content_html', 'de', false))->toBe('<p>Deutsche Rechnung</p>')
            ->and($stored->getTranslation('title', 'de', false))->toBe('Rechnung');

        File::deleteDirectory($directory);
    });
    it('keeps a change to a shared field saved from another site', function () {
        $site = $this->enableTranslation('de');
        $directory = $this->registerViewTemplates('acme', ['invoice' => "title = \"Invoice\"\n==\n<p>English invoice</p>"]);

        (new SyncTemplates)->handle();

        $template = $this->findTemplate('acme::pdf.invoice');

        actingAsPdfManager();

        Site::withContext($site->id, fn () => $this->saveTemplateForm($template->id, [
            'title' => 'Rechnung',
            'code' => 'acme::pdf.invoice',
            'content_html' => '<p>Deutsche Rechnung</p>',
            'size' => 'a5',
            'orientation' => 'landscape',
        ]))->assertOk();

        (new SyncTemplates)->handle();

        $stored = $this->findTemplate('acme::pdf.invoice');

        expect($stored->is_custom)->toBeTrue()
            ->and($stored->size)->toBe('a5')
            ->and($stored->orientation)->toBe('landscape')
            ->and($stored->title)->toBe('Invoice')
            ->and($stored->getTranslation('title', 'de', false))->toBe('Rechnung');

        File::deleteDirectory($directory);
    });
});
