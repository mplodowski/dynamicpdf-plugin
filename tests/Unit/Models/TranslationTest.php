<?php

use October\Rain\Support\Facades\Site;
use Renatio\DynamicPDF\Classes\SyncTemplates;
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
});
