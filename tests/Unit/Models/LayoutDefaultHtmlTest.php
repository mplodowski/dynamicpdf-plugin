<?php

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\File as Filesystem;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\DecoratedAdapter;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use October\Rain\Support\Facades\Yaml;
use Renatio\DynamicPDF\Classes\LocalFiles;
use Renatio\DynamicPDF\Models\Layout;
use System\Models\File;

describe('Default layout HTML', function () {
    beforeEach(function () {
        $this->uploads = sys_get_temp_dir() . '/dynamicpdf-uploads-' . uniqid();
        config(['filesystems.disks.uploads.root' => $this->uploads]);
        Storage::forgetDisk('uploads');
        $fields = Yaml::parseFile(plugins_path('renatio/dynamicpdf/models/layout/fields.yaml'));
        $this->layout = $this->createLayout([
            'code' => 'acme::pdf.layouts.default',
            'content_html' => $fields['secondaryTabs']['fields']['content_html']['default'],
        ]);

        $this->withBackground = function (): Layout {
            $image = new File;
            $image->fromData(base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='), 'bg.png');
            $this->layout->setAttribute('background_img', $image);
            $this->layout->save();

            return $this->layout->fresh();
        };
        $this->loadLayout = function (Layout $layout, ?array $chroot = null): string {
            $pdf = app('dynamicpdf');
            $pdf->getDomPDF()->getOptions()->setChroot($chroot ?? [base_path(), $this->uploads]);

            return $pdf->loadLayout($layout->code)->getDomPDF()->outputHtml();
        };
    });

    afterEach(function () {
        Storage::forgetDisk('uploads');
        Filesystem::deleteDirectory($this->uploads);
        Filesystem::deleteDirectory(LocalFiles::directory());
    });

    it('puts the local file of the background image on the body for the PDF', function () {
        $layout = ($this->withBackground)();

        expect(($this->loadLayout)($layout))
            ->toContain("<body style=\"background: url('" . $this->uploads . '/' . $layout->background_img->getDiskPath() . "')");
    });

    it('keeps the URL of a background outside the dompdf chroot', function () {
        $layout = ($this->withBackground)();

        expect(($this->loadLayout)($layout, [base_path()]))
            ->toContain("<body style=\"background: url('" . $layout->background_img->getPath() . "')");
    });

    it('puts the URL of the background image on the body for the browser preview and for parsed HTML', function () {
        $layout = ($this->withBackground)();
        $url = "<body style=\"background: url('" . $layout->background_img->getPath() . "')";

        expect(app('dynamicpdf')->forBrowser()->loadLayout($layout->code)->getDomPDF()->outputHtml())->toContain($url)
            ->and(tap(app('dynamicpdf'), fn ($pdf) => $pdf->getDomPDF()->getOptions()->setChroot([base_path(), $this->uploads]))->parseLayout($layout))->toContain($url);
    });

    it('copies a background image from a remote disk and deletes the copy with the PDF', function () {
        $layout = ($this->withBackground)();
        $remote = new class(new LocalFilesystemAdapter($this->uploads)) extends DecoratedAdapter
        {
        };
        Storage::set('uploads', new FilesystemAdapter(new Flysystem($remote), $remote));

        $pdf = app('dynamicpdf');
        preg_match("/url\\('([^']+)'\\)/", $pdf->loadLayout($layout->code)->getDomPDF()->outputHtml(), $match);
        $copy = $match[1] ?? '';

        expect(dirname($copy))->toBe(LocalFiles::directory())
            ->and(file_get_contents($copy))->toBe($layout->background_img->getContents());

        unset($pdf);

        expect(file_exists($copy))->toBeFalse();
    });

    it('leaves the body unstyled without one', function () {
        expect(app('dynamicpdf')->loadLayout($this->layout->code)->getDomPDF()->outputHtml())->toContain('<body>');
    });
});
