<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Renatio\DynamicPDF\Models\Template;

describe('Template', function () {
    it('finds a template by code', function () {
        $template = $this->createTemplate(['code' => 'test.find.template']);

        expect(Template::byCode('test.find.template')->id)->toBe($template->id);
    });

    it('throws when no template has the code', function () {
        expect(fn () => Template::byCode('non.existent.template'))
            ->toThrow(ModelNotFoundException::class);
    });

    it('belongs to a layout', function () {
        $layout = $this->createLayout();
        $template = $this->createTemplate(['layout_id' => $layout->id]);

        expect($template->layout?->id)->toBe($layout->id);
    });
});
