<?php

use Backend\Facades\Backend;
use Illuminate\Support\Facades\DB;
use October\Rain\Support\Facades\Flash;

describe('Template form', function () {
    beforeEach(fn () => actingAsPdfManager());

    it('names the configured dompdf paper defaults in the empty size and orientation options', function () {
        config([
            'dompdf.options.default_paper_size' => 'letter',
            'dompdf.options.default_paper_orientation' => 'landscape',
        ]);
        $template = $this->createTemplate(['size' => null, 'orientation' => null]);

        $html = $this->get(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id))->assertOk()->getContent();

        expect($html)->toContain('>' . trans('renatio.dynamicpdf::lang.options.default', ['value' => 'Letter']) . '<')
            ->toContain('>' . trans('renatio.dynamicpdf::lang.options.default', ['value' => trans('renatio.dynamicpdf::lang.orientation.landscape')]) . '<');
    });

    it('gives the icon-only list and form delete buttons an accessible name', function (string $path) {
        $template = $this->createTemplate();

        $html = $this->get(Backend::url(str_replace(':id', (string) $template->id, $path)))->assertOk()->getContent();

        expect($html)->toContain('aria-label="' . e(trans('backend::lang.form.delete')) . '"');
    })->with([
        'list' => ['renatio/dynamicpdf/templates'],
        'form' => ['renatio/dynamicpdf/templates/update/:id'],
    ]);
});

describe('Form buttons', function () {
    it('renders the record actions inside a change-monitored form', function (string $definition) {
        actingAsPdfManager();
        $record = $definition === 'templates' ? $this->createTemplate() : $this->createLayout();

        $html = $this->get(Backend::url("renatio/dynamicpdf/{$definition}/update/{$record->id}"))->assertOk()->getContent();

        expect($html)->toContain('data-change-monitor')
            ->toContain('data-request-before-update="$(this).trigger(\'unchange.oc.changeMonitor\')"')
            ->toContain('data-request="onDuplicate"')
            ->toContain('data-handler="onPreviewUnsaved"');
    })->with(['templates', 'layouts']);

    it('asks before Cancel or Duplicate leaves unsaved changes', function (string $definition) {
        actingAsPdfManager();
        $record = $definition === 'templates' ? $this->createTemplate() : $this->createLayout();
        $confirm = fn (string $key): string => e('oc.confirmPromise(' . json_encode(trans("renatio.dynamicpdf::lang.templates.{$key}")) . ')');

        expect($this->get(Backend::url("renatio/dynamicpdf/{$definition}/update/{$record->id}"))->assertOk()->getContent())
            ->toContain($confirm('cancel_unsaved'))
            ->toContain($confirm('duplicate_unsaved'));
    })->with(['templates', 'layouts']);

    it('speaks the backend language in the save flash and the form buttons', function () {
        actingAsPdfManager();
        $this->withSession(['locale' => 'es']);
        $template = $this->createTemplate();

        $this->saveTemplateForm($template->id, [
            'title' => 'Factura',
            'code' => $template->code,
            'content_html' => '<p>Factura</p>',
        ])->assertOk();

        expect(Flash::all())->toBe(['success' => 'El registro fue guardado.'])
            ->and($this->get(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id))->assertOk()->getContent())
            ->toContain('Guardar y cerrar')
            ->toContain('"Guardando..."')
            ->not->toContain('Save &amp; Close');
    });
});

describe('Variables list', function () {
    beforeEach(fn () => actingAsPdfManager());

    it('lists the saved sample data keys on the Options tab, escaped', function () {
        $template = $this->createTemplate(['sample_data' => '{"<b>x</b>": 1, "order": {"number": "FV/1"}}']);

        $html = $this->get(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id))->assertOk()->getContent();

        expect($html)->toContain('data-control="dynamicpdf-variables"')
            ->toContain('aria-labelledby="Form-field-Template-_variables-label"')
            ->toContain('id="Form-field-Template-_variables-label"')
            ->toContain('data-field-depends="[&quot;sample_data&quot;]"')
            ->toContain('data-snippet="{{ order.number }}"')
            ->toContain('>&lt;b&gt;x&lt;/b&gt;</button>')
            ->not->toContain('<b>x</b>');
    });

    it('shows how to add sample data when the stored JSON is invalid', function () {
        $template = $this->createTemplate();
        DB::table($template->getTable())->where('id', $template->id)->update(['sample_data' => '{"order": ']);

        $html = $this->get(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id))->assertOk()->getContent();

        expect($html)->toContain(e(trans('renatio.dynamicpdf::lang.variables.sample_data_empty')));
    });

    it('rebuilds the list from the unsaved sample data when the field refreshes', function () {
        $template = $this->createTemplate(['sample_data' => '{"order": {"number": "FV/1"}}']);

        $response = $this->post(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id), [
            'Template' => ['sample_data' => '{"invoice": {"total": 10}}'],
            'fields' => ['_variables'],
        ], [
            'X-AJAX-HANDLER' => 'form::onRefresh',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk();

        expect($response->getContent())->toContain('{{ invoice.total }}')
            ->not->toContain('{{ order.number }}');
    });
});
