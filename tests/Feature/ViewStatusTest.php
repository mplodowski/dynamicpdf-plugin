<?php

use Backend\Classes\FormField;
use October\Rain\Support\Facades\Site;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

describe('View status notice', function () {
    beforeEach(function () {
        actingAsPdfManager();
        PDFManager::instance()->registerTemplates(['renatio.dynamicpdf::pdf.invoice']);
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default']);
    });

    it('informs while a record follows its view and warns once it is customised', function (string $definition, string $view) {
        $model = $definition === 'templates'
            ? $this->createTemplate(['code' => $view, 'is_custom' => false])
            : $this->createLayout(['code' => $view, 'is_custom' => false]);

        expect(viewStatusField($model))
            ->hidden->toBeFalse()
            ->mode->toBe('info')
            ->getConfig('text')->toContain("plugin's default");

        $model->is_custom = true;
        $model->save();

        expect(viewStatusField($model))
            ->hidden->toBeFalse()
            ->mode->toBe('warning');
    })->with([
        'template' => ['templates', 'renatio.dynamicpdf::pdf.invoice'],
        'layout' => ['layouts', 'renatio.dynamicpdf::pdf.layouts.default'],
    ]);

    it('does not warn a translator that translated fields detach the record', function () {
        $site = $this->enableTranslation('de');
        $template = $this->createTemplate(['code' => 'renatio.dynamicpdf::pdf.invoice', 'is_custom' => false]);
        $layout = $this->createLayout(['code' => 'renatio.dynamicpdf::pdf.layouts.default', 'is_custom' => false]);

        $render = fn (Templates|Layouts $controller, int $id): string => Site::withContext($site->id, fn () => $controller->run('update', [$id])->getContent());

        expect($render(new Templates, $template->id))
            ->toContain('title and content are saved as a translation')
            ->not->toContain('title, description, content')
            ->and($render(new Layouts, $layout->id))
            ->toContain('HTML, CSS and page number text are saved as a translation')
            ->not->toContain('name, HTML, CSS');
    });

    it('shows no notice on a record created in the backend', function () {
        expect(viewStatusField($this->createTemplate()))->hidden->toBeTrue();
    });
});

function viewStatusField(Template|Layout $record): FormField
{
    $controller = $record instanceof Template ? new Templates : new Layouts;
    $controller->run('update', [$record->id]);

    return $controller->formGetWidget()->getField('_view_status');
}
