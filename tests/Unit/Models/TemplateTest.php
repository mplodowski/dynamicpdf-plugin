<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use October\Rain\Database\Model;
use October\Rain\Database\Traits\Validation;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

describe('Template Model', function () {
    describe('Class Structure', function () {
        it('extends Model', function () {
            $reflection = new ReflectionClass(Template::class);

            expect($reflection->getParentClass()?->getName())->toBe(Model::class);
        });

        it('uses Validation trait', function () {
            $traits = class_uses(Template::class);

            expect($traits)->toContain(Validation::class);
        });
    });

    describe('Table Configuration', function () {
        it('has correct table name', function () {
            $template = new Template;

            expect($template->getTable())->toBe('renatio_dynamicpdf_pdf_templates');
        });
    });

    describe('Relationships', function () {
        it('belongs to layout', function () {
            $template = new Template;

            expect($template->belongsTo)->toHaveKey('layout');
            expect($template->belongsTo['layout'])->toBe(Layout::class);
        });
    });

    describe('Validation Rules', function () {
        it('requires title', function () {
            $template = new Template;

            expect($template->rules)->toHaveKey('title');
            expect($template->rules['title'])->toContain('required');
        });

        it('requires code', function () {
            $template = new Template;

            expect($template->rules)->toHaveKey('code');
            expect($template->rules['code'])->toContain('required');
        });

        it('requires unique code', function () {
            $template = new Template;

            expect($template->rules['code'])->toContain('unique:renatio_dynamicpdf_pdf_templates');
        });

        it('requires content_html', function () {
            $template = new Template;

            expect($template->rules)->toHaveKey('content_html');
            expect($template->rules['content_html'])->toContain('required');
        });
    });

    describe('Methods', function () {
        it('has byCode method', function () {
            expect(method_exists(Template::class, 'byCode'))->toBeTrue();
        });

        it('has getSizeOptions method', function () {
            expect(method_exists(Template::class, 'getSizeOptions'))->toBeTrue();
        });

        it('has getOrientationOptions method', function () {
            expect(method_exists(Template::class, 'getOrientationOptions'))->toBeTrue();
        });

        it('has fillFromCode method', function () {
            expect(method_exists(Template::class, 'fillFromCode'))->toBeTrue();
        });

        it('has fillFromView method', function () {
            expect(method_exists(Template::class, 'fillFromView'))->toBeTrue();
        });

        it('has getView method', function () {
            expect(method_exists(Template::class, 'getView'))->toBeTrue();
        });

        it('has getHtmlAttribute method', function () {
            expect(method_exists(Template::class, 'getHtmlAttribute'))->toBeTrue();
        });
    });

    describe('Size Options', function () {
        it('returns array of paper sizes', function () {
            $sizes = Template::getSizeOptions();

            expect($sizes)->toBeArray();
            expect($sizes)->not->toBeEmpty();
        });

        it('includes common paper sizes', function () {
            $sizes = Template::getSizeOptions();

            expect($sizes)->toHaveKey('a4');
            expect($sizes)->toHaveKey('letter');
        });
    });

    describe('Orientation Options', function () {
        it('returns array of orientations', function () {
            $orientations = Template::getOrientationOptions();

            expect($orientations)->toBeArray();
        });

        it('includes portrait option', function () {
            $orientations = Template::getOrientationOptions();

            expect($orientations)->toHaveKey('portrait');
        });

        it('includes landscape option', function () {
            $orientations = Template::getOrientationOptions();

            expect($orientations)->toHaveKey('landscape');
        });
    });

    describe('CRUD Operations', function () {
        it('can create a template', function () {
            $template = $this->createTemplate();

            expect($template)->toBeInstanceOf(Template::class);
            expect($template->exists)->toBeTrue();
        });

        it('can find template by code', function () {
            $template = $this->createTemplate(['code' => 'test.find.template']);

            $found = Template::byCode('test.find.template');

            expect($found->id)->toBe($template->id);
        });

        it('throws exception when template not found by code', function () {
            expect(fn () => Template::byCode('non.existent.template'))
                ->toThrow(ModelNotFoundException::class);
        });

        it('can associate with layout', function () {
            $layout = $this->createLayout();
            $template = $this->createTemplate(['layout_id' => $layout->id]);

            expect($template->layout)->not->toBeNull();
            expect($template->layout->id)->toBe($layout->id);
        });
    });
});
