<?php

use Backend\Facades\Backend;
use Illuminate\Support\Facades\DB;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

describe('List actions', function () {
    beforeEach(fn () => actingAsPdfManager());

    it('duplicates a template from the list and redirects to the copy', function () {
        $template = $this->createTemplate(['code' => 'acme::pdf.invoice']);

        $response = $this->listAction('onDuplicateRecord', ['id' => $template->id, 'definition' => 'templates']);
        $copy = Template::whereCode('acme::pdf.invoice_copy')->firstOrFail();

        expect((string) $response->json('__ajax.redirect'))->toContain('templates/update/' . $copy->id);
    });

    it('treats a non-string list definition as the templates list', function () {
        $template = $this->createTemplate(['code' => 'acme::pdf.invoice']);

        $this->listAction('onDuplicateRecord', ['id' => $template->id, 'definition' => ['layouts']])->assertOk();

        expect(Template::whereCode('acme::pdf.invoice_copy')->exists())->toBeTrue();
    });

    it('resets a customised view-driven template from the list', function () {
        $this->registerViewTemplates('listactions', ['a' => "title = \"From view\"\n==\n<p>from view</p>"]);
        $template = $this->createTemplate(['code' => 'listactions::pdf.a', 'title' => 'Edited', 'content_html' => '<p>edited</p>', 'is_custom' => true]);

        $this->listAction('onResetRecord', ['id' => $template->id, 'definition' => 'templates'])->assertOk();

        $row = DB::table('renatio_dynamicpdf_pdf_templates')->where('id', $template->id)->first();

        expect($row?->is_custom)->toBeFalsy()
            ->and((string) $row?->content_html)->toContain('from view');
    });

    it('offers the reset only for a customised view-driven template', function () {
        $this->registerViewTemplates('listactions', [
            'a' => "title = \"Untouched\"\n==\n<p>a</p>",
            'b' => "title = \"From view\"\n==\n<p>b</p>",
        ]);
        $untouched = $this->createTemplate(['code' => 'listactions::pdf.a', 'is_custom' => false]);
        $customised = $this->createTemplate(['code' => 'listactions::pdf.b', 'title' => 'Edited', 'is_custom' => true]);

        $list = (new Templates)->run('index', ['templates'])->getContent();

        expect(substr_count($list, 'data-request="onResetRecord"'))->toBe(1)
            ->and($list)->toContain(e(trans('renatio.dynamicpdf::lang.templates.reset_confirm', ['name' => 'Edited'])))
            ->and($list)->not->toContain('data-request="onDeleteRecord"')
            ->and((new Templates)->run('update', [$untouched->id])->getContent())->not->toContain('onResetDefault')
            ->and((new Templates)->run('update', [$customised->id])->getContent())->toContain('onResetDefault');
    });

    it('refuses to delete a template that follows a view file', function () {
        $this->registerViewTemplates('listactions', ['a' => "title = \"From view\"\n==\n<p>from view</p>"]);
        $template = $this->createTemplate(['code' => 'listactions::pdf.a', 'is_custom' => false]);

        $response = $this->listAction('onDeleteRecord', ['id' => $template->id, 'definition' => 'templates']);

        expect($response->getContent())->toContain(trans('renatio.dynamicpdf::lang.templates.delete_view_refused'))
            ->and(Template::find($template->id))->not->toBeNull();
    });

    it('reports a record that no longer exists with a translated message', function (string $definition, string $model) {
        $response = $this->listAction('onDeleteRecord', ['id' => 999999, 'definition' => $definition]);

        expect($response->json('__ajax.message'))->toBe(trans('backend::lang.model.not_found', ['class' => $model, 'id' => 999999]));
    })->with([
        'template' => ['templates', Template::class],
        'layout' => ['layouts', Layout::class],
    ]);

    it('deletes a locked layout whose view registration is gone', function () {
        $layout = $this->createLayout(['code' => 'gone::pdf.layouts.default', 'is_locked' => true]);

        $this->listAction('onDeleteRecord', ['id' => $layout->id, 'definition' => 'layouts'])->assertOk();

        expect(Layout::find($layout->id))->toBeNull();
    });

    it('refuses to delete a layout that templates use', function (string $path, string $handler) {
        $layout = $this->createLayout();
        $this->createTemplate(['title' => "O'Brien & Sons <b>invoice</b>", 'layout_id' => $layout->id]);

        $response = $this->post(Backend::url(str_replace(':id', (string) $layout->id, $path)), [
            'id' => $layout->id,
            'definition' => 'layouts',
        ], ['X-AJAX-HANDLER' => $handler, 'X-Requested-With' => 'XMLHttpRequest']);

        expect($response->json('__ajax.message'))->toContain("O'Brien & Sons <b>invoice</b>")
            ->and(Layout::find($layout->id))->not->toBeNull()
            ->and(Template::whereLayoutId($layout->id)->exists())->toBeTrue();
    })->with([
        'from the list' => ['renatio/dynamicpdf/templates', 'onDeleteRecord'],
        'from the form' => ['renatio/dynamicpdf/layouts/update/:id', 'onDelete'],
    ]);

    it('disables deleting a layout that templates use and names the count', function () {
        $used = $this->createLayout(['name' => 'Used layout']);
        $this->createLayout(['name' => 'Free layout']);
        $this->createTemplate(['layout_id' => $used->id]);
        $this->createTemplate(['layout_id' => $used->id]);

        $list = (new Templates)->run('index', ['layouts'])->getContent();
        $form = (new Layouts)->run('update', [$used->id])->getContent();
        $confirm = fn (string $name): string => e(trans('renatio.dynamicpdf::lang.templates.delete_confirm', ['name' => $name]));

        expect($list)->toContain($confirm('Free layout'))
            ->not->toContain($confirm('Used layout'))
            ->toContain('Used by templates: 2')
            ->and($form)->toContain('Used by templates: 2')
            ->not->toContain($confirm('Used layout'));
    });

    it('duplicates a layout from the list with manage_layouts', function () {
        $layout = $this->createLayout(['code' => 'acme::pdf.layouts.default']);

        $this->listAction('onDuplicateRecord', ['id' => $layout->id, 'definition' => 'layouts'])->assertOk();

        expect(Layout::whereCode('acme::pdf.layouts.default_copy')->exists())->toBeTrue();
    });

    it('duplicates a layout that templates use without copying the templates', function () {
        $layout = $this->createLayout(['code' => 'acme::pdf.layouts.default']);
        $this->createTemplate(['layout_id' => $layout->id]);

        $copy = $layout->duplicate();

        expect(Template::count())->toBe(1)
            ->and(Template::whereLayoutId($copy->id)->exists())->toBeFalse();
    });
});
