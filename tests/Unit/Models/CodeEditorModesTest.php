<?php

use October\Rain\Support\Facades\Yaml;

describe('Code editor fields', function () {
    it('use the language the plugin renders the field with, from a mode Ace ships', function (string $model, string $field, string $language) {
        $config = Yaml::parseFile(plugins_path("renatio/dynamicpdf/models/{$model}/fields.yaml"));
        $actual = $config['secondaryTabs']['fields'][$field]['language'];

        expect($actual)->toBe($language)
            ->and(base_path("modules/backend/formwidgets/codeeditor/assets/vendor/ace/mode-{$actual}.js"))->toBeFile();
    })->with([
        'template HTML' => ['template', 'content_html', 'twig'],
        'template sample data' => ['template', 'sample_data', 'javascript'],
        'layout HTML' => ['layout', 'content_html', 'twig'],
        'layout CSS' => ['layout', 'content_css', 'less'],
    ]);
});
