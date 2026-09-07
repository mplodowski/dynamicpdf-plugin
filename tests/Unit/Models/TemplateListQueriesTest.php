<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Models\Template;

describe('Template list queries', function () {
    beforeEach(function () {
        $this->views = sys_get_temp_dir() . '/dynamicpdf-views-' . uniqid();
        File::makeDirectory($this->views . '/pdf', 0755, true);
        View::addNamespace('acmetest', $this->views);

        foreach (['a', 'b', 'c'] as $name) {
            File::put($this->views . "/pdf/{$name}.htm", "title = \"{$name}\"\nlayout = \"acme::pdf.layouts.default\"\n==\n<p>{$name}</p>");
        }

        PDFManager::instance()->registerTemplates(['acmetest::pdf.a', 'acmetest::pdf.b', 'acmetest::pdf.c']);
    });

    afterEach(function () {
        PDFManager::forgetInstance();
        File::deleteDirectory($this->views);
    });

    it('resolves the layout of view-driven templates once per code, not once per row', function () {
        $this->createLayout(['code' => 'acme::pdf.layouts.default']);
        foreach (['a', 'b', 'c'] as $name) {
            $this->createTemplate(['code' => "acmetest::pdf.{$name}", 'is_custom' => false]);
        }

        DB::enableQueryLog();
        $templates = Template::query()->get();
        $layoutQueries = collect(DB::getQueryLog())->filter(fn (array $q): bool => str_contains($q['query'], 'renatio_dynamicpdf_pdf_layouts'))->count();
        DB::disableQueryLog();

        expect($templates)->toHaveCount(3)
            ->and($templates->first()?->layout?->code)->toBe('acme::pdf.layouts.default')
            ->and($templates->first()?->layout === $templates->last()?->layout)->toBeFalse()
            ->and($layoutQueries)->toBe(1);
    });

    it('does not remember a layout that only exists as a registered view', function () {
        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.default']);
        File::put($this->views . '/pdf/a.htm', "title = \"a\"\nlayout = \"renatio.dynamicpdf::pdf.layouts.default\"\n==\n<p>a</p>");
        $this->createTemplate(['code' => 'acmetest::pdf.a', 'is_custom' => false]);

        $template = Template::byCode('acmetest::pdf.a');

        expect($template->layout?->exists)->toBeFalse()
            ->and(Template::layoutCache()->count())->toBe(0);
    });

    it('forgets a remembered layout when it is saved', function () {
        $layout = $this->createLayout(['code' => 'acme::pdf.layouts.default', 'name' => 'Before']);
        $this->createTemplate(['code' => 'acmetest::pdf.a', 'is_custom' => false]);
        Template::query()->get();

        $layout->name = 'After';
        $layout->save();

        expect(Template::byCode('acmetest::pdf.a')->layout?->name)->toBe('After');
    });
});
