<?php

use Illuminate\Support\Facades\File as Filesystem;
use October\Rain\Support\Facades\Yaml;
use System\Models\File;

describe('Default layout HTML', function () {
    beforeEach(function () {
        $this->uploads = sys_get_temp_dir() . '/dynamicpdf-uploads-' . uniqid();
        config(['filesystems.disks.uploads.root' => $this->uploads]);
        $fields = Yaml::parseFile(plugins_path('renatio/dynamicpdf/models/layout/fields.yaml'));
        $this->layout = $this->createLayout([
            'code' => 'acme::pdf.layouts.default',
            'content_html' => $fields['secondaryTabs']['fields']['content_html']['default'],
        ]);
    });

    afterEach(fn () => Filesystem::deleteDirectory($this->uploads));

    it('puts the uploaded background image on the body', function () {
        $image = new File;
        $image->fromData(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='), 'bg.png');
        $this->layout->setAttribute('background_img', $image);
        $this->layout->save();

        $layout = $this->layout->fresh();

        expect(app('dynamicpdf')->parseLayout($layout))
            ->toContain("<body style=\"background: url('" . $layout->background_img->getPath() . "')");
    });

    it('leaves the body unstyled without one', function () {
        expect(app('dynamicpdf')->parseLayout($this->layout))->toContain('<body>');
    });
});
