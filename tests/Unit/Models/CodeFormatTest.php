<?php

use October\Rain\Exception\ValidationException;

describe('Code format', function () {
    it('refuses a code with spaces', function (string $create) {
        expect(fn () => $this->{$create}(['code' => 'review test code with spaces']))
            ->toThrow(ValidationException::class, trans('renatio.dynamicpdf::lang.templates.code_format'));
    })->with(['createTemplate', 'createLayout']);

    it('accepts the codes registered views and their copies use', function (string $create, string $code) {
        expect($this->{$create}(['code' => $code])->exists)->toBeTrue();
    })->with(['createTemplate', 'createLayout'])->with([
        'acme.shop::pdf.order_confirmation',
        'renatio.dynamicpdf::pdf.layouts.default_copy2',
        'Acme.Shop::pdf/order-confirmation',
        'pdf.invoice',
    ]);

    it('keeps a stored row with an older free-form code editable', function (string $create) {
        $model = $this->{$create}();
        $model->code = 'review test code with spaces';
        $model->forceSave();

        $model = $model::findOrFail($model->id);
        $model->content_html = '<p>Edited</p>';

        expect($model->save())->toBeTrue();
    })->with(['createTemplate', 'createLayout']);

    it('duplicates a stored row with an older free-form code', function (string $create) {
        $model = $this->{$create}();
        $model->code = 'review test code with spaces';
        $model->forceSave();

        expect($model::findOrFail($model->id)->duplicate()->code)->toBe('review_test_code_with_spaces_copy');
    })->with(['createTemplate', 'createLayout']);
});
