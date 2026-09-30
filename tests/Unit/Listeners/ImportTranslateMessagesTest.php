<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Mockery\MockInterface;
use October\Rain\Support\Facades\Site;
use RainLab\Translate\Classes\ThemeScanner;

/**
 * @param  array<int, string>  $expected
 * @param  array<int, string>  $unexpected
 */
function expectImportedMessages(array $expected, array $unexpected = []): MockInterface
{
    $scanner = Mockery::mock(ThemeScanner::class)->makePartial();
    $scanner->shouldReceive('importMessages')->once()->with(Mockery::on(function (array $messages) use ($expected, $unexpected): bool {
        expect($messages)->toContain(...$expected);

        foreach ($unexpected as $message) {
            expect($messages)->not->toContain($message);
        }

        return true;
    }));

    return $scanner;
}

describe('RainLab.Translate theme scan', function () {
    afterEach(function () {
        if (isset($this->views)) {
            File::deleteDirectory($this->views);
        }
    });

    it('imports the messages of layouts, custom templates and the current view of view-driven templates', function () {
        $this->views = $this->registerViewTemplates('scanviews', ['a' => "title = \"A\"\n==\n<p>{{ 'From view'|_ }}</p>"]);
        $this->createTemplate(['code' => 'scanviews::pdf.a', 'is_custom' => false, 'content_html' => "<p>{{ 'Stale column'|_ }}</p>"]);
        $this->createTemplate(['content_html' => "<p>{{ 'Custom template'|_ }}</p>"]);
        $this->createLayout(['content_html' => "<footer>{{ 'Layout footer'|_ }}</footer>{{ content_html }}"]);

        Event::fire('rainlab.translate.themeScanner.afterScan', [expectImportedMessages(['From view', 'Custom template', 'Layout footer'], ['Stale column'])]);
    });

    it('imports layouts and templates in the locale of the active site', function () {
        $site = $this->enableTranslation('de');

        $layout = $this->createLayout(['content_html' => "<footer>{{ 'Layout EN'|_ }}</footer>{{ content_html }}"]);
        $layout->setTranslation('content_html', 'de', "<footer>{{ 'Layout DE'|_ }}</footer>{{ content_html }}");
        $layout->save();

        $template = $this->createTemplate(['content_html' => "<p>{{ 'Template EN'|_ }}</p>"]);
        $template->setTranslation('content_html', 'de', "<p>{{ 'Template DE'|_ }}</p>");
        $template->save();

        $scanner = expectImportedMessages(['Layout DE', 'Template DE']);

        Site::withContext($site->id, fn () => Event::fire('rainlab.translate.themeScanner.afterScan', [$scanner]));
    });
})->skip(fn () => ! class_exists(ThemeScanner::class), 'RainLab.Translate is not installed');
