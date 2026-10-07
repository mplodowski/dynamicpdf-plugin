<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use October\Rain\Exception\ApplicationException;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

describe('Registered view fallback', function () {
    beforeEach(function () {
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default']);
    });

    it('builds a template from its registered view when the record does not exist', function () {
        $template = Template::byCode('renatio.dynamicpdf::pdf.invoice');

        expect($template->exists)->toBeFalse()
            ->and($template->is_custom)->toBeFalse()
            ->and($template->title)->toBe('Invoice')
            ->and($template->layout?->name)->toBe('Default Layout');
    });

    it('renders the template inside its registered layout', function () {
        $html = app('dynamicpdf')->parseTemplate(Template::byCode('renatio.dynamicpdf::pdf.invoice'));

        expect($html)->toStartWith('<!DOCTYPE html>')
            ->and($html)->toContain('</body>');
    });

    it('builds a layout from its registered view when the record does not exist', function () {
        $layout = Layout::byCode('renatio.dynamicpdf::pdf.layouts.default');

        expect($layout->exists)->toBeFalse()
            ->and($layout->code)->toBe('renatio.dynamicpdf::pdf.layouts.default')
            ->and($layout->name)->toBe('Default Layout');
    });

    it('still throws for a code that is neither stored nor registered', function (Closure $find) {
        expect($find)->toThrow(ModelNotFoundException::class);
    })->with([
        'template' => [fn (): Template => Template::byCode('non.existent::pdf.view')],
        'layout' => [fn (): Layout => Layout::byCode('non.existent::pdf.view')],
    ]);

    it('names a view without a title or name after its code', function () {
        $directory = $this->registerViewTemplates('untitled', ['bare' => "description = \"Bare\"\n==\n<p>Bare</p>"]);
        $this->registerViewLayouts('untitled', ['frame' => "description = \"Frame\"\n==\n==\n<html>{{ content_html|raw }}</html>"], $directory);

        expect(Template::byCode('untitled::pdf.bare')->title)->toBe('untitled::pdf.bare')
            ->and(Layout::byCode('untitled::pdf.frame')->name)->toBe('untitled::pdf.frame');
    });

    it('puts the missing code into the not found message', function (Template|Layout $model, string $message) {
        $model->code = 'gone::pdf.view';

        expect(fn () => $model->fillFromCode())->toThrow(ApplicationException::class, $message);
    })->with([
        'template' => [fn (): Template => new Template, 'Unable to find a registered template with code gone::pdf.view.'],
        'layout' => [fn (): Layout => new Layout, 'Unable to find a registered layout with code gone::pdf.view.'],
    ]);
});
