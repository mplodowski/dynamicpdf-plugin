<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File as Filesystem;
use Illuminate\Support\Facades\Storage;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use System\Models\File;

describe('dynamicpdf:export and dynamicpdf:import', function () {
    beforeEach(function () {
        $this->enableTranslation('de');

        $this->directory = sys_get_temp_dir() . '/dynamicpdf-export-' . uniqid();
        $this->uploads = "{$this->directory}/uploads";
        $this->path = "{$this->directory}/export.json";
        config(['filesystems.disks.uploads.root' => $this->uploads]);
        Storage::forgetDisk('uploads');

        $background = new File;
        $background->fromData(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='), 'bg.png');

        $layout = $this->createLayout([
            'code' => 'acme::pdf.layouts.base',
            'content_html' => '<html><head><style>{{ css|raw }}</style></head><body style="background: url(\'{{ background_img }}\')">EN-LAYOUT {{ content_html|raw }}</body></html>',
            'content_css' => '@color: #123456; p { color: @color; }',
            'page_numbers' => 'bottom-right',
        ]);
        $layout->setTranslation('content_html', 'de', '<html><head><style>{{ css|raw }}</style></head><body style="background: url(\'{{ background_img }}\')">DE-LAYOUT {{ content_html|raw }}</body></html>');
        $layout->setAttribute('background_img', $background);
        $layout->save();

        $template = $this->createTemplate([
            'code' => 'acme::pdf.invoice',
            'content_html' => '<p>EN-BODY {{ customer }}</p>',
            'sample_data' => '{"customer": "Ada"}',
            'layout_id' => $layout->id,
        ]);
        $template->setTranslation('content_html', 'de', '<p>DE-BODY {{ customer }}</p>');
        $template->save();

        $this->render = function (?string $locale = null): string {
            $template = $this->findTemplate('acme::pdf.invoice');

            return app('dynamicpdf')->loadTemplate($template->code, $template->sampleData(), locale: $locale)->getDomPDF()->outputHtml();
        };
        $this->import = function (array $options = []): int {
            return Artisan::call('dynamicpdf:import', ['file' => $this->path, ...$options]);
        };
        $this->exportAndWipe = function (?callable $change = null): void {
            Artisan::call('dynamicpdf:export', ['--path' => $this->path]);
            Template::query()->get()->each->delete();
            Layout::query()->get()->each->delete();

            if ($change !== null) {
                Filesystem::put($this->path, json_encode($change(json_decode(Filesystem::get($this->path), true)), JSON_THROW_ON_ERROR));
            }
        };
    });

    afterEach(function () {
        Storage::forgetDisk('uploads');
        Filesystem::deleteDirectory($this->directory);
    });

    it('moves a template with its layout, translations, sample data and background so it renders identically', function () {
        $before = [($this->render)(), ($this->render)('de')];
        $oldBackground = $this->findLayout('acme::pdf.layouts.base')->background_img;
        $oldPaths = [$oldBackground->getPath(), $oldBackground->getDiskPath()];
        $contents = $oldBackground->getContents();

        expect(Artisan::call('dynamicpdf:export', ['codes' => ['acme::pdf.invoice'], '--path' => $this->path]))->toBe(0);

        $this->findTemplate('acme::pdf.invoice')->delete();
        $this->findLayout('acme::pdf.layouts.base')->delete();

        expect(($this->import)())->toBe(0);

        $background = $this->findLayout('acme::pdf.layouts.base')->background_img;
        $newPaths = [$background->getPath(), $background->getDiskPath()];

        expect($before[0])->toContain($oldPaths[1])->toContain('color: #123456')
            ->and($before[1])->toContain('DE-LAYOUT')->toContain('DE-BODY Ada')
            ->and($background->getContents())->toBe($contents)
            ->and(($this->render)())->toBe(str_replace($oldPaths, $newPaths, $before[0]))
            ->and(($this->render)('de'))->toBe(str_replace($oldPaths, $newPaths, $before[1]));
    });

    it('skips existing codes unless forced, then overwrites content, translations and background', function () {
        Artisan::call('dynamicpdf:export', ['--path' => $this->path]);

        $template = $this->findTemplate('acme::pdf.invoice');
        $template->content_html = '<p>EDITED</p>';
        $template->setTranslation('content_html', 'de', '<p>DE-EDITED</p>');
        $template->save();
        $oldFile = $this->uploads . '/' . $this->findLayout('acme::pdf.layouts.base')->background_img->getDiskPath();

        ($this->import)();

        expect(Artisan::output())->toContain('skipped')
            ->and(file_exists($oldFile))->toBeTrue()
            ->and($this->findTemplate('acme::pdf.invoice')->content_html)->toBe('<p>EDITED</p>');

        ($this->import)(['--force' => true]);

        $template = $this->findTemplate('acme::pdf.invoice');

        expect(Artisan::output())->toContain('updated')
            ->and($template->content_html)->toBe('<p>EN-BODY {{ customer }}</p>')
            ->and($template->getTranslation('content_html', 'de', false))->toBe('<p>DE-BODY {{ customer }}</p>')
            ->and(File::where('attachment_type', Layout::class)->count())->toBe(1)
            ->and(file_exists($oldFile))->toBeFalse();
    });

    it('rejects a file in an unknown format', function () {
        Filesystem::ensureDirectoryExists($this->directory);
        Filesystem::put($this->path, json_encode(['format' => 'something-else', 'templates' => []], JSON_THROW_ON_ERROR));

        expect(($this->import)())->toBe(1)
            ->and(Artisan::output())->toContain('not a Renatio.DynamicPDF export');
    });

    it('imports a record whose view the target does not register as customized, so the sync keeps it', function () {
        ($this->exportAndWipe)(function (array $payload): array {
            $payload['layouts'][0]['is_locked'] = true;
            $payload['templates'][0]['is_custom'] = false;

            return $payload;
        });
        ($this->import)();
        $views = $this->registerViewTemplates('other', ['letter' => "title = \"Letter\"\n==\n<p>Letter</p>"]);

        (new SyncTemplates)->handle();
        Filesystem::deleteDirectory($views);

        expect($this->findTemplate('acme::pdf.invoice')->is_custom)->toBeTrue()
            ->and($this->findLayout('acme::pdf.layouts.base')->is_locked)->toBeFalse();
    });

    it('exports a layout whose background file is missing without the background and warns about it', function () {
        $layout = $this->findLayout('acme::pdf.layouts.base');
        Filesystem::delete($this->uploads . '/' . $layout->background_img->getDiskPath());

        expect(Artisan::call('dynamicpdf:export', ['--path' => $this->path]))->toBe(0)
            ->and(Artisan::output())->toContain('acme::pdf.layouts.base')
            ->and(json_decode(Filesystem::get($this->path), true)['layouts'][0]['background'])->toBeNull();
    });

    it('reports an unexpected error without a stack trace and imports nothing', function () {
        ($this->exportAndWipe)();
        Template::saving(fn () => throw new RuntimeException('Disk full'));

        expect(($this->import)())->toBe(1)
            ->and(Artisan::output())->toContain('Disk full Nothing was imported.')
            ->and(Layout::query()->count())->toBe(0);
    });

    it('rolls back the whole import and its background files when one record is invalid', function (Closure $change, string $error) {
        ($this->exportAndWipe)($change);

        expect(($this->import)())->toBe(1)
            ->and(Artisan::output())->toContain($error)
            ->and(Layout::query()->count())->toBe(0)
            ->and(File::query()->count())->toBe(0)
            ->and(Filesystem::allFiles($this->uploads))->toBe([]);
    })->with([
        'invalid Twig' => [fn (array $payload) => data_set($payload, 'templates.0.content_html', '<p>{% if %}</p>'), 'acme::pdf.invoice'],
        'invalid Twig in a translation' => [fn (array $payload) => data_set($payload, 'templates.0.translations.de.content_html', '<p>{% if %}</p>'), 'de translation is invalid'],
        'too long translated title' => [fn (array $payload) => data_set($payload, 'templates.0.translations.de.title', str_repeat('a', 256)), 'de translation is invalid'],
        'background with a non-image extension' => [fn (array $payload) => data_set($payload, 'layouts.0.background.file_name', 'bg.php'), 'must be a jpg'],
        'background content not matching its extension' => [fn (array $payload) => data_set($payload, 'layouts.0.background.file_name', 'bg.gif'), 'not image/gif'],
        'empty background' => [fn (array $payload) => data_set($payload, 'layouts.0.background', []), 'invalid structure'],
        'duplicate codes' => [function (array $payload) {
            $payload['templates'][] = $payload['templates'][0];

            return $payload;
        }, 'duplicate'],
    ]);
});
