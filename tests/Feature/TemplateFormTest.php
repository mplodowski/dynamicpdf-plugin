<?php

use Backend\Facades\Backend;
use Illuminate\Support\Facades\DB;
use October\Rain\Support\Facades\Flash;

describe('Template form', function () {
    it('names the configured dompdf paper defaults in the empty size and orientation options', function () {
        actingAsPdfManager();
        config([
            'dompdf.options.default_paper_size' => 'letter',
            'dompdf.options.default_paper_orientation' => 'landscape',
        ]);
        $template = $this->createTemplate(['size' => null, 'orientation' => null]);

        $html = $this->get(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id))->assertOk()->getContent();

        expect($html)->toContain('>' . trans('renatio.dynamicpdf::lang.options.default', ['value' => 'Letter']) . '<')
            ->toContain('>' . trans('renatio.dynamicpdf::lang.options.default', ['value' => trans('renatio.dynamicpdf::lang.orientation.landscape')]) . '<');
    });
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

    it('asks before Cancel or Duplicate leaves unsaved changes and always before Duplicate', function (string $definition) {
        actingAsPdfManager();
        $record = $definition === 'templates' ? $this->createTemplate(['title' => 'Invoice']) : $this->createLayout(['name' => 'Invoice']);
        $message = fn (string $key, array $replace = []): string => (string) json_encode(trans("renatio.dynamicpdf::lang.templates.{$key}", $replace));
        $branches = fn (string $unsaved, string $saved): string => e("form.hasClass('oc-data-changed') ? {$unsaved} : {$saved}; if (!message) return; oc.confirmPromise(message)");

        expect($this->get(Backend::url("renatio/dynamicpdf/{$definition}/update/{$record->id}"))->assertOk()->getContent())
            ->toContain($branches($message('cancel_unsaved'), 'null'))
            ->toContain($branches($message('duplicate_unsaved'), $message('duplicate_confirm', ['name' => 'Invoice'])));
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

    it('uses the same record wording in the layout save flash', function () {
        actingAsPdfManager();
        $this->withSession(['locale' => 'es']);
        $layout = $this->createLayout();

        $this->saveLayoutForm($layout->id, [
            'name' => 'Diseño',
            'code' => $layout->code,
            'content_html' => (string) $layout->content_html,
            'content_css' => '',
        ])->assertOk();

        expect(Flash::all())->toBe(['success' => 'El registro fue guardado.']);
    });
});

describe('Editor suggestions', function () {
    beforeEach(fn () => actingAsPdfManager());

    it('passes the saved sample data keys to the HTML editor, escaped', function () {
        $template = $this->createTemplate(['sample_data' => '{"<b>x</b>": 1, "order": {"number": "FV/1"}}']);

        $html = $this->get(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id))->assertOk()->getContent();

        expect($html)->toContain('data-control="dynamicpdf-variables"')
            ->toContain('data-field-depends="[&quot;sample_data&quot;]"')
            ->toContain('&quot;{{ order.number }}&quot;')
            ->toContain(e(trans('renatio.dynamicpdf::lang.variables.comment')))
            ->not->toContain('<b>x</b>');
    });

    it('suggests what a layout receives instead of sample data', function () {
        $layout = $this->createLayout();

        $html = $this->get(Backend::url('renatio/dynamicpdf/layouts/update/' . $layout->id))->assertOk()->getContent();

        expect($html)->toContain('data-control="dynamicpdf-variables"')
            ->toContain('&quot;{{ content_html|raw }}&quot;')
            ->not->toContain('data-field-depends="[&quot;sample_data&quot;]"');
    });

    it('still opens the form when the stored sample data is invalid', function () {
        $template = $this->createTemplate();
        DB::table($template->getTable())->where('id', $template->id)->update(['sample_data' => '{"order": ']);

        $this->get(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id))->assertOk();
    });

    it('rebuilds the suggestions from the unsaved sample data when the field refreshes', function () {
        $template = $this->createTemplate(['sample_data' => '{"order": {"number": "FV/1"}}']);

        $response = $this->post(Backend::url('renatio/dynamicpdf/templates/update/' . $template->id), [
            'Template' => ['sample_data' => '{"invoice": {"total": 10}}'],
            'fields' => ['_variables'],
        ], [
            'X-AJAX-HANDLER' => 'form::onRefresh',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk();

        expect($response->getContent())->toContain('invoice.total')
            ->not->toContain('order.number');
    });
});
