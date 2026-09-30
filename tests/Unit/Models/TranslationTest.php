<?php

use October\Rain\Support\Facades\Site;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

describe('Native model translation', function () {
    describe('view-driven template with a translation', function () {
        beforeEach(function () {
            $this->site = $this->enableTranslation('de');
            $this->directory = $this->registerViewTemplates('acme', ['invoice' => "title = \"Invoice\"\n==\n<p>English invoice</p>"]);

            (new SyncTemplates)->handle();

            $template = $this->findTemplate('acme::pdf.invoice');
            $template->setTranslation('content_html', 'de', '<p>Deutsche Rechnung</p>');
            $template->save();
        });

        afterEach(fn () => File::deleteDirectory($this->directory));

        it('keeps the translation when the view refills the template on fetch', function () {
            $fetched = Site::withContext($this->site->id, fn (): Template => $this->findTemplate('acme::pdf.invoice'));

            expect($fetched->content_html)->toBe('<p>Deutsche Rechnung</p>')
                ->and($fetched->getTranslation('content_html', 'en'))->toBe('<p>English invoice</p>');
        });

        it('rewrites only the base value when the view file changes under the sync', function () {
            File::put("{$this->directory}/pdf/invoice.htm", "title = \"Invoice\"\n==\n<p>Revised invoice</p>");

            Site::withContext($this->site->id, fn () => (new SyncTemplates)->handle());

            $stored = Db::table('renatio_dynamicpdf_pdf_templates')->where('code', 'acme::pdf.invoice')->first();

            expect($stored->content_html)->toBe('<p>Revised invoice</p>')
                ->and($this->findTemplate('acme::pdf.invoice')->getTranslation('content_html', 'de', false))
                ->toBe('<p>Deutsche Rechnung</p>');
        });

        it('does not rewrite a template whose view is unchanged but whose translation differs', function () {
            $sync = new SyncTemplates;
            Site::withContext($this->site->id, fn () => $sync->handle());

            expect($sync->report()['updated'])->toBe([]);
        });
    });

    it('rewrites only the base value of a locked layout when its view file changes under the sync', function () {
        $site = $this->enableTranslation('de');
        $directory = $this->registerViewLayouts('acme', ['layouts/base' => "name = \"Base\"\n==\n<p>English layout</p>"]);

        (new SyncTemplates)->handle();

        $layout = $this->findLayout('acme::pdf.layouts.base');
        $layout->setTranslation('content_html', 'de', '<p>Deutsches Layout</p>');
        $layout->save();

        File::put("{$directory}/pdf/layouts/base.htm", "name = \"Base\"\n==\n<p>Revised layout</p>");

        Site::withContext($site->id, fn () => (new SyncTemplates)->handle());

        $fetched = Site::withContext($site->id, fn (): Layout => $this->findLayout('acme::pdf.layouts.base'));
        File::deleteDirectory($directory);

        expect(Db::table('renatio_dynamicpdf_pdf_layouts')->where('code', 'acme::pdf.layouts.base')->value('content_html'))->toBe('<p>Revised layout</p>')
            ->and($fetched->content_html)->toBe('<p>Deutsches Layout</p>')
            ->and($fetched->is_locked)->toBeTrue();
    });

    it('renders the template and its layout in the requested locale', function () {
        $this->enableTranslation('de');

        $layout = $this->createLayout(['code' => 'acme::pdf.layouts.base', 'content_html' => '<html><body>EN-LAYOUT {{ content_html }}</body></html>']);
        $layout->setTranslation('content_html', 'de', '<html><body>DE-LAYOUT {{ content_html }}</body></html>');
        $layout->save();

        $template = $this->createTemplate(['code' => 'acme::pdf.invoice', 'content_html' => 'EN-BODY', 'layout_id' => $layout->id]);
        $template->setTranslation('content_html', 'de', 'DE-BODY');
        $template->save();

        $html = app('dynamicpdf')->loadTemplate('acme::pdf.invoice', locale: 'de')->getDomPDF()->outputHtml();

        expect($html)->toContain('DE-LAYOUT')->toContain('DE-BODY');
    });

    it('ignores stored translations when the feature is off', function () {
        $site = $this->createSite('de');

        $template = $this->createTemplate(['code' => 'acme::pdf.invoice', 'content_html' => 'EN-BODY']);
        $template->setTranslation('content_html', 'de', 'DE-BODY');
        $template->save();

        $html = app('dynamicpdf')->loadTemplate('acme::pdf.invoice', locale: 'de')->getDomPDF()->outputHtml();
        $onSite = Site::withContext($site->id, fn (): ?string => $this->findTemplate('acme::pdf.invoice')->content_html);

        expect($html)->toContain('EN-BODY')
            ->and($onSite)->toBe('EN-BODY');
    });

    it('copies the translations of a duplicated template and layout', function () {
        $this->enableTranslation('de');

        $template = $this->createTemplate(['code' => 'acme::pdf.invoice', 'content_html' => 'EN-BODY']);
        $template->setTranslation('content_html', 'de', 'DE-BODY');
        $template->save();

        $layout = $this->createLayout(['code' => 'acme::pdf.layouts.base']);
        $layout->setTranslation('content_html', 'de', 'DE-LAYOUT');
        $layout->save();

        $templateCopy = $this->findTemplate('acme::pdf.invoice')->duplicate();
        $layoutCopy = $this->findLayout('acme::pdf.layouts.base')->duplicate();

        expect($this->findTemplate($templateCopy->code)->getTranslation('content_html', 'de', false))->toBe('DE-BODY')
            ->and($this->findLayout($layoutCopy->code)->getTranslation('content_html', 'de', false))->toBe('DE-LAYOUT');
    });

    it('removes the translations of a template the sync deletes', function () {
        $this->enableTranslation('de');
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        $template = $this->createTemplate(['code' => 'gone::pdf.stale', 'is_custom' => false]);
        $template->setTranslation('content_html', 'de', 'DE-BODY');
        $template->save();

        (new SyncTemplates)->handle();

        expect(Template::whereCode('gone::pdf.stale')->exists())->toBeFalse()
            ->and(Db::table('system_translate_attributes')->where('model_type', Template::class)->where('model_id', $template->id)->exists())->toBeFalse();
    });

    it('removes the translations of the demo templates when the demo is disabled', function () {
        $this->enableTranslation('de');
        Artisan::call('dynamicpdf:demo');
        $template = $this->findTemplate('renatio.dynamicpdf::pdf.invoice');
        $template->setTranslation('content_html', 'de', 'DE-BODY');
        $template->save();

        Artisan::call('dynamicpdf:demo', ['--disable' => true]);

        expect(Db::table('system_translate_attributes')->where('model_type', Template::class)->where('model_id', $template->id)->exists())->toBeFalse();
    });

    it('restores the model locale when the default-locale callback throws', function () {
        $this->enableTranslation('de');
        $template = $this->createTemplate();
        $template->setLocale('de');

        expect(fn () => $template->inDefaultLocale(fn () => throw new RuntimeException))->toThrow(RuntimeException::class)
            ->and($template->getLocale())->toBe('de');
    });
});
