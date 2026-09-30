<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use RainLab\Translate\Classes\ThemeScanner;

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

        $scanner = Mockery::mock(ThemeScanner::class)->makePartial();
        $scanner->shouldReceive('importMessages')->once()->with(Mockery::on(function (array $messages): bool {
            expect($messages)->toContain('From view', 'Custom template', 'Layout footer')
                ->not->toContain('Stale column');

            return true;
        }));

        Event::fire('rainlab.translate.themeScanner.afterScan', [$scanner]);
    });
});
