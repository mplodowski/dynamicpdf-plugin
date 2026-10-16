<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\PDFParser;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Plugin;
use System\Models\Parameter;

describe('unlock_edited_layouts', function () {
    beforeEach(function () {
        $replaceIsLocked = require plugins_path('renatio/dynamicpdf/updates/20261012_0001_replace_is_locked_with_is_custom_on_layouts.php');
        $replaceIsLocked->down();

        $this->migrate = fn () => (require plugins_path('renatio/dynamicpdf/updates/20260930_0001_unlock_edited_layouts.php'))->up();
        $this->replaceIsLocked = fn () => $replaceIsLocked->up();

        $this->insertLayout = fn (string $code, string $name, string $html, ?string $css = null): int => DB::table('renatio_dynamicpdf_pdf_layouts')->insertGetId([
            'code' => $code, 'name' => $name, 'content_html' => $html, 'content_css' => $css, 'is_locked' => true,
        ]);

        $this->row = fn (int $id): object => DB::table('renatio_dynamicpdf_pdf_layouts')->where('id', $id)->first();

        $shipped = (new PDFParser)->parseContent(File::get(__DIR__ . '/../../fixtures/header_and_footer-8.0.3.htm'));
        $this->shipped = [$shipped['settings']['name'], $shipped['html'], $shipped['css']];

        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.header_and_footer']);
        $this->registerViewLayouts('unlockviews', ['layouts/letter' => "name = \"Letter\"\n==\np { color: red; }\n==\n<p>v2</p>"]);
    });

    it('keeps a layout that holds its current view locked, whatever its line endings', function () {
        $id = ($this->insertLayout)('unlockviews::pdf.layouts.letter', 'Letter', "<p>v2</p>\r\n", "p { color: red; }\r\n");

        ($this->migrate)();

        expect(($this->row)($id)->is_locked)->toBeTruthy();
    });

    it('keeps a layout locked that differs from its view only in indentation and line breaks', function () {
        $this->registerViewLayouts('unlockspaces', ['layouts/letter' => "name = \"Letter\"\n==\np { color: red; }\n==\n<div class=\"head\">\n<strong>A</strong>\n<strong>B</strong>\n</div>"]);
        $id = ($this->insertLayout)('unlockspaces::pdf.layouts.letter', 'Letter', "<div class=\"head\">\n        <strong>A</strong> <strong>B</strong>\n</div>", 'p {  color: red; }');

        ($this->migrate)();

        expect(($this->row)($id)->is_locked)->toBeTruthy();
    });

    it('keeps an unedited layout of an older plugin release locked and the sync then brings it up to date', function () {
        $id = ($this->insertLayout)('renatio.dynamicpdf::pdf.layouts.header_and_footer', ...$this->shipped);

        ($this->migrate)();
        ($this->replaceIsLocked)();
        (new SyncTemplates)->handle();

        $current = (new PDFParser)->parseView('renatio.dynamicpdf::pdf.layouts.header_and_footer');

        expect(($this->row)($id))
            ->is_custom->toBeFalsy()
            ->content_html->toBe($current['html'])
            ->content_css->toBe($current['css'])
            ->and($this->shipped[1])->not->toBe($current['html']);
    });

    it('unlocks an edited layout still flagged as locked so the sync never overwrites it', function () {
        [$name, $html, $css] = $this->shipped;
        $edited = str_replace('</body>', '<p>Our footer</p></body>', $html);
        $id = ($this->insertLayout)('renatio.dynamicpdf::pdf.layouts.header_and_footer', $name, $edited, $css);

        ($this->migrate)();
        ($this->replaceIsLocked)();
        (new SyncTemplates)->handle();

        expect(($this->row)($id))
            ->is_custom->toBeTruthy()
            ->content_html->toBe($edited);
    });

    it('unlocks a layout whose third-party view changed since it was created and leaves its content alone', function () {
        $id = ($this->insertLayout)('unlockviews::pdf.layouts.letter', 'Letter', '<p>v1</p>', 'p { color: red; }');

        ($this->migrate)();
        ($this->replaceIsLocked)();
        (new SyncTemplates)->handle();

        expect(($this->row)($id))
            ->is_custom->toBeTruthy()
            ->content_html->toBe('<p>v1</p>');
    });

    it('unlocks an edited layout whose view is registered only after it ran so the sync never overwrites it', function () {
        $id = ($this->insertLayout)('lateviews::pdf.layouts.letter', 'Letter', '<p>mine</p>');

        ($this->migrate)();

        $this->registerViewLayouts('lateviews', ['layouts/letter' => "name = \"Letter\"\n==\n<p>v2</p>"]);
        ($this->replaceIsLocked)();
        (new SyncTemplates)->handle();

        expect(($this->row)($id))
            ->is_custom->toBeTruthy()
            ->content_html->toBe('<p>mine</p>');
    });

    it('keeps an unedited layout of this plugin locked while its view is not registered', function () {
        $id = ($this->insertLayout)('renatio.dynamicpdf::pdf.layouts.header_and_footer', ...$this->shipped);
        PDFManager::forgetInstance();
        Parameter::set(Plugin::DEMO_PARAMETER, 0);

        ($this->migrate)();

        expect(($this->row)($id)->is_locked)->toBeTruthy();
    });

    it('unlocks every edited layout when the registered layouts cannot be listed', function () {
        $id = ($this->insertLayout)('unlockviews::pdf.layouts.letter', 'Letter', '<p>mine</p>');
        $manager = Mockery::mock(PDFManager::class);
        $manager->shouldReceive('listRegisteredLayouts')->andThrow(new RuntimeException('broken registration'));
        (new ReflectionProperty(PDFManager::class, 'instance'))->setValue(null, $manager);

        ($this->migrate)();

        expect(($this->row)($id)->is_locked)->toBeFalsy();
    });

    it('gives the same result when it runs twice', function () {
        $unedited = ($this->insertLayout)('unlockviews::pdf.layouts.letter', 'Letter', '<p>v2</p>', 'p { color: red; }');
        $edited = ($this->insertLayout)('renatio.dynamicpdf::pdf.layouts.header_and_footer', 'Header and Footer', '<p>edited</p>');

        ($this->migrate)();
        ($this->migrate)();

        expect(($this->row)($unedited)->is_locked)->toBeTruthy()
            ->and(($this->row)($edited)->is_locked)->toBeFalsy();
    });
});
