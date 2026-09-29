<?php

use October\Rain\Exception\ValidationException;

describe('Code format', function () {
    it('refuses a code with spaces', function (string $create) {
        try {
            $this->{$create}(['code' => 'review test code with spaces']);
        } catch (ValidationException $e) {
            expect($e->getErrors()->first('code'))->toBe(trans('renatio.dynamicpdf::lang.templates.code_format'));

            return;
        }

        $this->fail('The code with spaces was saved.');
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
});
