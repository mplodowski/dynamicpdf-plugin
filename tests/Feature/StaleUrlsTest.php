<?php

use Backend\Facades\Backend;

describe('Stale and unknown URLs', function () {
    beforeEach(fn () => actingAsPdfManager());

    it('opens the templates tab for a tab it does not know', function (string $tab) {
        $html = $this->get(Backend::url("renatio/dynamicpdf/templates/index/{$tab}"))->assertOk()->getContent();

        expect($html)->toMatch('/<li class="active">\s*<a href="#templates"/');
    })->with(['foo', 'Templates']);

    it('opens the layouts tab whatever the case of its name', function () {
        $html = $this->get(Backend::url('renatio/dynamicpdf/templates/index/Layouts'))->assertOk()->getContent();

        expect($html)->toMatch('/<li class="active">\s*<a href="#layouts"/');
    });

    it('sends the bare layouts URL to the layouts tab', function () {
        $this->get(Backend::url('renatio/dynamicpdf/layouts'))
            ->assertRedirect(Backend::url('renatio/dynamicpdf/templates/index/layouts'));
    });

    it('answers the HTML preview of a deleted record with a 404 that names it', function (string $definition) {
        $response = $this->get(Backend::url("renatio/dynamicpdf/{$definition}/html/999999"));

        expect($response->status())->toBe(404)
            ->and($response->getContent())->toContain('999999');
    })->with(['templates', 'layouts']);

    it('answers the HTML preview without an id with a 404 that says so', function () {
        $response = $this->get(Backend::url('renatio/dynamicpdf/templates/html'));

        expect($response->status())->toBe(404)
            ->and($response->getContent())->toContain(e(trans('backend::lang.form.missing_id')));
    });

    it('shows the PDF preview error page for a URL without an id', function (string $definition) {
        $response = $this->get(Backend::url("renatio/dynamicpdf/{$definition}/previewpdf"));

        expect($response->status())->toBe(200)
            ->and($response->getContent())->toContain(e(trans('backend::lang.form.missing_id')));
    })->with(['templates', 'layouts']);
});
