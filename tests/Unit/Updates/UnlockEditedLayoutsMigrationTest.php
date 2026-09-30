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
        $this->migrate = fn () => (require plugins_path('renatio/dynamicpdf/updates/20260930_0001_unlock_edited_layouts.php'))->up();

        $this->insertLayout = fn (string $code, string $name, string $html, ?string $css = null): int => DB::table('renatio_dynamicpdf_pdf_layouts')->insertGetId([
            'code' => $code, 'name' => $name, 'content_html' => $html, 'content_css' => $css, 'is_locked' => true,
        ]);

        $this->row = fn (int $id): object => DB::table('renatio_dynamicpdf_pdf_layouts')->where('id', $id)->first();

        $shipped = (new PDFParser)->parseContent(File::get(__DIR__ . '/../../fixtures/header_and_footer-8.0.3.htm'));
        $this->shipped = [$shipped['settings']['name'], $shipped['html'], $shipped['css']];

        PDFManager::instance()->registerLayouts(['renatio.dynamicpdf::pdf.layouts.header_and_footer']);
        $this->views = $this->registerViewLayouts('unlockviews', ['layouts/letter' => "name = \"Letter\"\n==\np { color: red; }\n==\n<p>v2</p>"]);
    });

    afterEach(function () {
        PDFManager::forgetInstance();
        File::deleteDirectory($this->views);
    });

    it('keeps a layout that holds its current view locked, whatever its line endings', function () {
        $id = ($this->insertLayout)('unlockviews::pdf.layouts.letter', 'Letter', "<p>v2</p>\r\n", "p { color: red; }\r\n");

        ($this->migrate)();

        expect(($this->row)($id)->is_locked)->toBeTruthy();
    });

    it('keeps an unedited layout of an older plugin release locked and the sync then brings it up to date', function () {
        $id = ($this->insertLayout)('renatio.dynamicpdf::pdf.layouts.header_and_footer', ...$this->shipped);

        ($this->migrate)();
        (new SyncTemplates)->handle();

        $current = (new PDFParser)->parseView('renatio.dynamicpdf::pdf.layouts.header_and_footer');

        expect(($this->row)($id))
            ->is_locked->toBeTruthy()
            ->content_html->toBe($current['html'])
            ->content_css->toBe($current['css'])
            ->and($this->shipped[1])->not->toBe($current['html']);
    });

    it('unlocks an edited layout still flagged as locked so the sync never overwrites it', function () {
        [$name, $html, $css] = $this->shipped;
        $edited = str_replace('</body>', '<p>Our footer</p></body>', $html);
        $id = ($this->insertLayout)('renatio.dynamicpdf::pdf.layouts.header_and_footer', $name, $edited, $css);

        ($this->migrate)();
        (new SyncTemplates)->handle();

        expect(($this->row)($id))
            ->is_locked->toBeFalsy()
            ->content_html->toBe($edited);
    });

    it('unlocks a layout whose third-party view changed since it was created and leaves its content alone', function () {
        $id = ($this->insertLayout)('unlockviews::pdf.layouts.letter', 'Letter', '<p>v1</p>', 'p { color: red; }');

        ($this->migrate)();
        (new SyncTemplates)->handle();

        expect(($this->row)($id))
            ->is_locked->toBeFalsy()
            ->content_html->toBe('<p>v1</p>');
    });

    it('unlocks an edited layout whose view is registered only after it ran so the sync never overwrites it', function () {
        $id = ($this->insertLayout)('lateviews::pdf.layouts.letter', 'Letter', '<p>mine</p>');

        ($this->migrate)();

        $views = $this->registerViewLayouts('lateviews', ['layouts/letter' => "name = \"Letter\"\n==\n<p>v2</p>"]);
        (new SyncTemplates)->handle();
        File::deleteDirectory($views);

        expect(($this->row)($id))
            ->is_locked->toBeFalsy()
            ->content_html->toBe('<p>mine</p>');
    });

    it('keeps an unedited layout of this plugin locked while its view is not registered', function () {
        $id = ($this->insertLayout)('renatio.dynamicpdf::pdf.layouts.header_and_footer', ...$this->shipped);
        PDFManager::forgetInstance();
        Parameter::set(Plugin::DEMO_PARAMETER, 0);

        ($this->migrate)();

        expect(($this->row)($id)->is_locked)->toBeTruthy();
    });

    it('unlocks every edited layout and still lets layouts follow their view when the registered layouts cannot be listed', function () {
        $id = ($this->insertLayout)('unlockviews::pdf.layouts.letter', 'Letter', '<p>mine</p>');
        Parameter::set(Plugin::LAYOUTS_FOLLOW_VIEWS_PARAMETER, 0);
        $manager = Mockery::mock(PDFManager::class);
        $manager->shouldReceive('listRegisteredLayouts')->andThrow(new RuntimeException('broken registration'));
        (new ReflectionProperty(PDFManager::class, 'instance'))->setValue(null, $manager);

        ($this->migrate)();

        expect(($this->row)($id)->is_locked)->toBeFalsy()
            ->and(Parameter::get(Plugin::LAYOUTS_FOLLOW_VIEWS_PARAMETER))->toBeTruthy();
    });

    it('lets layouts follow their view only once it has run', function () {
        Parameter::set(Plugin::LAYOUTS_FOLLOW_VIEWS_PARAMETER, 0);

        ($this->migrate)();

        expect(Parameter::get(Plugin::LAYOUTS_FOLLOW_VIEWS_PARAMETER))->toBeTruthy();
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
