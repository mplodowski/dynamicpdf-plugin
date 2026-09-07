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

    it('survives a fetch without the code column', function () {
        $this->createTemplate(['code' => 'partial::pdf.select', 'is_custom' => false]);

        expect(Template::whereCode('partial::pdf.select')->value('is_custom'))->toBeFalsy();
    });

    it('belongs to a layout', function () {
        $layout = $this->createLayout();
        $template = $this->createTemplate(['layout_id' => $layout->id]);

        expect($template->layout?->id)->toBe($layout->id);
    });
});
