<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

describe('Output helpers', function () {
    beforeEach(function () {
        $this->uploads = sys_get_temp_dir() . '/dynamicpdf-uploads-' . uniqid();
        config(['filesystems.disks.uploads.root' => $this->uploads]);
        Storage::forgetDisk('uploads');
    });

    afterEach(function () {
        Storage::forgetDisk('uploads');
        File::deleteDirectory($this->uploads);
    });

    it('turns the document into an attachable file record', function () {
        $wrapper = app('dynamicpdf');
        $wrapper->loadHTML('<p>Hello</p>');

        $file = $wrapper->toFile('hello.pdf');
        $stored = File::allFiles($this->uploads);

        expect((string) $file->getAttribute('file_name'))->toBe('hello.pdf')
            ->and((string) $file->getAttribute('content_type'))->toBe('application/pdf')
            ->and((int) $file->getAttribute('file_size'))->toBeGreaterThan(0)
            ->and($stored)->toHaveCount(1)
            ->and(file_get_contents($stored[0]->getPathname()))->toStartWith('%PDF');
    });

    it('stores a protected file where a protected relation expects it', function () {
        $wrapper = app('dynamicpdf');
        $wrapper->loadHTML('<p>Hello</p>');

        $file = $wrapper->toFile('hello.pdf', public: false);

        expect($file->getAttribute('is_public'))->toBeFalsy()
            ->and(File::allFiles($this->uploads . '/protected'))->toHaveCount(1);
    });

    it('encrypts the document and stays chainable', function () {
        $wrapper = app('dynamicpdf');
        $wrapper->loadHTML('<p>Secret</p>');

        $output = $wrapper->encrypt('reader', 'owner')->output();

        expect($output)->toContain('/Encrypt');
    });
});
