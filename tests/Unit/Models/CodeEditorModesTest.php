<?php

use Illuminate\Support\Arr;
use October\Rain\Support\Facades\Yaml;

describe('Code editor fields', function () {
    it('only use languages Ace ships a mode for', function (string $model) {
        $config = Yaml::parseFile(plugins_path("renatio/dynamicpdf/models/{$model}/fields.yaml"));

        $modes = collect(Arr::only($config, ['fields', 'tabs', 'secondaryTabs']))
            ->flatMap(fn (array $section) => $section['fields'] ?? $section)
            ->where('type', 'codeeditor')
            ->map(fn (array $field) => base_path("modules/backend/formwidgets/codeeditor/assets/vendor/ace/mode-{$field['language']}.js"));

        expect($modes)->not->toBeEmpty()->each->toBeFile();
    })->with(['template', 'layout']);
});
