<?php

use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Models\Template;

function renderHtml(string $code, ?string $locale = null): string
{
    return app('dynamicpdf')->loadTemplate($code, locale: $locale)->getDomPDF()->outputHtml();
}

describe('Localized view files', function () {
    beforeEach(function () {
        PDFManager::forgetInstance();
        SyncTemplates::forgetFailures();
    });

    afterEach(function () {
        PDFManager::forgetInstance();
        SyncTemplates::forgetFailures();
        File::deleteDirectory($this->directory);
    });

    describe('template', function () {
        beforeEach(function () {
            $this->enableTranslation('de');

            $this->directory = $this->registerViewTemplates('localized', [
                'invoice' => "title = \"Invoice\"\n==\n<p>English invoice</p>",
            ]);
            $this->writeViewFiles('localized', [
                'de/invoice' => "title = \"Rechnung\"\n==\n<p>Deutsche Rechnung</p>",
            ], $this->directory);

            (new SyncTemplates)->handle();
        });

        it('renders the sibling view of the locale and the base view without one', function () {
            expect(renderHtml('localized::pdf.invoice', 'de'))->toContain('Deutsche Rechnung')
                ->and(renderHtml('localized::pdf.invoice'))->toContain('English invoice');
        });

        it('falls back along the locale chain', function () {
            expect(renderHtml('localized::pdf.invoice', 'de-AT'))->toContain('Deutsche Rechnung');
        });

        it('ignores the sibling view of a customised template', function () {
            $template = $this->findTemplate('localized::pdf.invoice');
            $template->is_custom = true;
            $template->content_html = '<p>Customised</p>';
            $template->save();

            expect(renderHtml('localized::pdf.invoice', 'de'))->toContain('Customised');
        });

        it('prefers a stored translation over the sibling view', function () {
            $template = $this->findTemplate('localized::pdf.invoice');
            $template->setTranslation('content_html', 'de', '<p>Gespeicherte Rechnung</p>');
            $template->save();

            expect(renderHtml('localized::pdf.invoice', 'de'))->toContain('Gespeicherte Rechnung');
        });

        it('keeps the registered code and saves nothing', function () {
            $template = Template::byCode('localized::pdf.invoice');
            $template->fillFromLocalizedView('de');

            expect($template->code)->toBe('localized::pdf.invoice')
                ->and($template->content_html)->toBe('<p>Deutsche Rechnung</p>')
                ->and(Db::table('renatio_dynamicpdf_pdf_templates')->where('code', 'localized::pdf.invoice')->value('content_html'))
                ->toBe('<p>English invoice</p>');
        });
    });

    it('renders a template with the sibling view of its layout', function () {
        $this->enableTranslation('de');

        $this->directory = $this->registerViewLayouts('localizedlayout', [
            'layouts/default' => "name = \"Default\"\n==\n<html><body>EN-LAYOUT {{ content_html }}</body></html>",
        ]);
        $this->writeViewFiles('localizedlayout', [
            'layouts/de/default' => "name = \"Standard\"\n==\n<html><body>DE-LAYOUT {{ content_html }}</body></html>",
        ], $this->directory);
        $this->registerViewTemplates('localizedlayout', [
            'invoice' => "title = \"Invoice\"\nlayout = \"localizedlayout::pdf.layouts.default\"\n==\n<p>Body</p>",
        ], $this->directory);

        (new SyncTemplates)->handle();

        expect(renderHtml('localizedlayout::pdf.invoice', 'de'))->toContain('DE-LAYOUT')->toContain('Body');
    });

    it('keeps the layout given to loadTemplate() over the one of the sibling view', function () {
        $this->enableTranslation('de');

        $this->directory = $this->registerViewLayouts('overridelayout', [
            'layouts/default' => "name = \"Default\"\n==\n<html><body>DEFAULT {{ content_html }}</body></html>",
            'layouts/plain' => "name = \"Plain\"\n==\n<html><body>PLAIN {{ content_html }}</body></html>",
        ]);
        $this->registerViewTemplates('overridelayout', [
            'invoice' => "title = \"Invoice\"\nlayout = \"overridelayout::pdf.layouts.default\"\n==\n<p>Body</p>",
        ], $this->directory);
        $this->writeViewFiles('overridelayout', [
            'de/invoice' => "title = \"Rechnung\"\nlayout = \"overridelayout::pdf.layouts.default\"\n==\n<p>Deutsch</p>",
        ], $this->directory);

        (new SyncTemplates)->handle();

        $html = app('dynamicpdf')->loadTemplate('overridelayout::pdf.invoice', layout: 'overridelayout::pdf.layouts.plain', locale: 'de')->getDomPDF()->outputHtml();

        expect($html)->toContain('PLAIN')->toContain('Deutsch')->not->toContain('DEFAULT');
    });
});
