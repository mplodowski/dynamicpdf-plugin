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

    it('answers a preview of a missing record without a server error', function (string $path) {
        $response = $this->get(Backend::url("renatio/dynamicpdf/{$path}"));

        expect($response->status())->toBeLessThan(500);
    })->with([
        'template HTML of a deleted record' => 'templates/html/999999',
        'layout HTML of a deleted record' => 'layouts/html/999999',
        'template HTML without an id' => 'templates/html',
        'template PDF without an id' => 'templates/previewpdf',
        'layout PDF without an id' => 'layouts/previewpdf',
    ]);
});
