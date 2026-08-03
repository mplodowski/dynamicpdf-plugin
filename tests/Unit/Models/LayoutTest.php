<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use Renatio\DynamicPDF\Models\Layout;

describe('Layout Model', function () {
    describe('Class Structure', function () {
        it('extends Model', function () {
            $reflection = new ReflectionClass(Layout::class);

            expect($reflection->getParentClass()?->getName())->toBe(Model::class);
        });

        it('uses Validation trait', function () {
            $traits = class_uses(Layout::class);

            expect($traits)->toContain(Validation::class);
        });
    });

    describe('Table Configuration', function () {
        it('has correct table name', function () {
            $layout = new Layout;

            expect($layout->getTable())->toBe('renatio_dynamicpdf_pdf_layouts');
        });
    });

    describe('Validation Rules', function () {
        it('requires name', function () {
            $layout = new Layout;

            expect($layout->rules)->toHaveKey('name');
            expect($layout->rules['name'])->toContain('required');
        });

        it('requires code', function () {
            $layout = new Layout;

            expect($layout->rules)->toHaveKey('code');
            expect($layout->rules['code'])->toContain('required');
        });

        it('requires unique code', function () {
            $layout = new Layout;

            expect($layout->rules['code'])->toContain('unique:renatio_dynamicpdf_pdf_layouts');
        });

        it('requires content_html', function () {
            $layout = new Layout;

            expect($layout->rules)->toHaveKey('content_html');
            expect($layout->rules['content_html'])->toContain('required');
        });
    });

    describe('Attachments', function () {
        it('has background_img attachOne relation', function () {
            $layout = new Layout;

            expect($layout->attachOne)->toHaveKey('background_img');
        });
    });

    describe('Methods', function () {
        it('has byCode method', function () {
            expect(method_exists(Layout::class, 'byCode'))->toBeTrue();
        });

        it('has getCSS method', function () {
            expect(method_exists(Layout::class, 'getCSS'))->toBeTrue();
        });

        it('has fillFromCode method', function () {
            expect(method_exists(Layout::class, 'fillFromCode'))->toBeTrue();
        });

        it('has fillFromView method', function () {
            expect(method_exists(Layout::class, 'fillFromView'))->toBeTrue();
        });

        it('has getView method', function () {
            expect(method_exists(Layout::class, 'getView'))->toBeTrue();
        });

        it('has getHtmlAttribute method', function () {
            expect(method_exists(Layout::class, 'getHtmlAttribute'))->toBeTrue();
        });
    });

    describe('CRUD Operations', function () {
        it('can create a layout', function () {
            $layout = $this->createLayout();

            expect($layout)->toBeInstanceOf(Layout::class);
            expect($layout->exists)->toBeTrue();
        });

        it('can find layout by code', function () {
            $layout = $this->createLayout(['code' => 'test.find.layout']);

            $found = Layout::byCode('test.find.layout');

            expect($found->id)->toBe($layout->id);
        });

        it('throws exception when layout not found by code', function () {
            expect(fn () => Layout::byCode('non.existent.layout'))
                ->toThrow(ModelNotFoundException::class);
        });
    });

    describe('CSS Processing', function () {
        it('getCSS returns string', function () {
            $layout = $this->createLayout(['content_css' => 'body { color: red; }']);

            expect($layout->getCSS())->toBeString();
        });

        it('getCSS processes LESS syntax', function () {
            $layout = $this->createLayout(['content_css' => '@color: red; body { color: @color; }']);
            $css = $layout->getCSS();

            expect($css)->toContain('color');
        });

        it('getCSS returns empty string when content_css is null', function () {
            $layout = $this->createLayout();
            $layout->content_css = null;

            expect($layout->getCSS())->toBe('');
        });
    });
});
