<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Renatio\DynamicPDF\Models\Layout;

describe('Layout', function () {
    it('finds a layout by code', function () {
        $layout = $this->createLayout(['code' => 'test.find.layout']);

        expect(Layout::byCode('test.find.layout')->id)->toBe($layout->id);
    });

    it('throws when no layout has the code', function () {
        expect(fn () => Layout::byCode('non.existent.layout'))
            ->toThrow(ModelNotFoundException::class);
    });

    it('compiles LESS in content_css', function () {
        $layout = $this->createLayout(['content_css' => '@color: red; body { color: @color; }']);

        expect($layout->getCSS())->toContain('color: red');
    });

    it('returns an empty string when content_css is null', function () {
        $layout = $this->createLayout();
        $layout->content_css = null;

        expect($layout->getCSS())->toBe('');
    });
});
