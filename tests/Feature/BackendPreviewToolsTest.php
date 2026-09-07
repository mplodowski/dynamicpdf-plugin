<?php

use Illuminate\Support\Facades\File as Filesystem;
use Illuminate\Support\Facades\View;
use October\Rain\Exception\ValidationException;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Models\File;

describe('Backend preview tools', function () {
    it('renders the HTML preview with the sample data of the template', function () {
        $template = $this->createTemplate(['content_html' => '<p>Hello {{ name }}</p>', 'sample_data' => '{"name": "Jane"}']);

        expect((new Templates)->html($template->id)->getContent())->toContain('<p>Hello Jane</p>');
    });

    it('rejects sample data that is not JSON', function () {
        expect(fn () => $this->createTemplate(['sample_data' => '{name: Jane']))->toThrow(ValidationException::class);
    });

    it('duplicates a template with a unique code, the same layout and a customised flag', function () {
        $layout = $this->createLayout();
        $template = $this->createTemplate(['code' => 'acme::pdf.invoice', 'layout_id' => $layout->id, 'is_custom' => false, 'content_html' => '<p>{{ x }}</p>']);
        $this->createTemplate(['code' => 'acme::pdf.invoice_copy']);

        $response = (new Templates)->update_onDuplicate($template->id);
        $copy = Template::whereCode('acme::pdf.invoice_copy2')->firstOrFail();

        expect($copy->getAttribute('is_custom'))->toBeTruthy()
            ->and((int) $copy->getAttribute('layout_id'))->toBe($layout->id)
            ->and((string) $copy->getAttribute('content_html'))->toBe('<p>{{ x }}</p>')
            ->and($response->getTargetUrl())->toContain('templates/update/' . $copy->id);
    });

    it('duplicates a layout as an unlocked copy with its background image', function () {
        $uploads = sys_get_temp_dir() . '/dynamicpdf-uploads-' . uniqid();
        config(['filesystems.disks.uploads.root' => $uploads]);
        $layout = $this->createLayout(['code' => 'acme::pdf.layouts.default', 'is_locked' => true]);
        $image = new File;
        $image->fromData(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='), 'bg.png');
        $layout->setAttribute('background_img', $image);
        $layout->save();

        (new Layouts)->update_onDuplicate($layout->id);
        $copy = Layout::whereCode('acme::pdf.layouts.default_copy')->firstOrFail();
        Filesystem::deleteDirectory($uploads);

        expect($copy->getAttribute('is_locked'))->toBeFalsy()
            ->and((string) $copy->getAttribute('name'))->toBe('Test Layout (copy)')
            ->and(File::where('attachment_id', $copy->id)->where('field', 'background_img')->count())->toBe(1);
    });

    it('keeps a view-driven template view-driven when only the sample data changes', function () {
        $template = $this->createTemplate(['is_custom' => false]);
        $template->sample_data = '{"name": "Jane"}';

        (new Templates)->formBeforeSave($template);

        expect($template->is_custom)->toBeFalse();

        $template->content_html = '<p>edited</p>';
        (new Templates)->formBeforeSave($template);

        expect($template->is_custom)->toBeTrue();
    });

    it('links the layout list to the layout previews', function () {
        expect(file_get_contents(__DIR__ . '/../../models/layout/columns.yaml'))->toContain('path: column_preview_layout')
            ->and(file_get_contents(__DIR__ . '/../../controllers/templates/_column_preview_layout.php'))->toContain('renatio/dynamicpdf/layouts/preview/');
    });
});

describe('View-driven template save', function () {
    beforeEach(function () {
        $this->views = sys_get_temp_dir() . '/dynamicpdf-views-' . uniqid();
        Filesystem::makeDirectory($this->views . '/pdf', 0755, true);
        View::addNamespace('viewdriven', $this->views);
        Filesystem::put($this->views . '/pdf/a.htm', "title = \"a\"\n==\n<p>v1</p>");
        PDFManager::instance()->registerTemplates(['viewdriven::pdf.a']);
    });

    afterEach(function () {
        PDFManager::forgetInstance();
        Filesystem::deleteDirectory($this->views);
    });

    it('stays view-driven when only the sample data changes after the view file changed on disk', function () {
        $this->createTemplate(['code' => 'viewdriven::pdf.a', 'is_custom' => false, 'content_html' => '<p>v1</p>', 'title' => 'a']);
        Filesystem::put($this->views . '/pdf/a.htm', "title = \"a\"\n==\n<p>v2</p>");

        $template = Template::byCode('viewdriven::pdf.a');
        $template->sample_data = '{"name": "Jane"}';
        (new Templates)->formBeforeSave($template);

        expect((string) $template->content_html)->toBe('<p>v2</p>')
            ->and($template->is_custom)->toBeFalse();
    });
});
