<?php

use Backend\Facades\Backend;

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
            ->toMatch('/<a(?=[^>]*\shref="' . preg_quote(Backend::url("renatio/dynamicpdf/{$definition}/previewpdf/{$record->id}"), '/') . '")(?=[^>]*\starget="_blank")/');
    })->with(['templates', 'layouts']);
});
