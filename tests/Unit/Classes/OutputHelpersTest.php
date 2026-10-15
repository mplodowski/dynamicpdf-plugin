<?php

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Renatio\DynamicPDF\Classes\PDFWrapper;
use Symfony\Component\Mime\Email;

describe('Output helpers', function () {
    beforeEach(function () {
        $this->uploads = $this->temporaryDirectory('uploads');
        config(['filesystems.disks.uploads.root' => $this->uploads]);
        Storage::forgetDisk('uploads');
    });

    afterEach(fn () => Storage::forgetDisk('uploads'));

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

    it('attaches the rendered document to an e-mail as a PDF', function () {
        $message = new Message(new Email);

        $message->attach(app('dynamicpdf')->loadHTML('<p>Hello</p>')->attachment('invoice.pdf'));
        $attachment = $message->getSymfonyMessage()->getAttachments()[0];

        expect($attachment->getFilename())->toBe('invoice.pdf')
            ->and($attachment->getContentType())->toBe('application/pdf')
            ->and($attachment->getBody())->toStartWith('%PDF');
    });

    it('renders the e-mail attachment only when it is added to a message', function () {
        $wrapper = new class(app('dompdf'), app('config'), app('files'), app('view')) extends PDFWrapper
        {
            public int $renders = 0;

            public function render(): void
            {
                $this->renders++;
                parent::render();
            }
        };

        $attachment = $wrapper->loadHTML('<p>Hello</p>')->attachment();
        $rendersBefore = $wrapper->renders;
        (new Message(new Email))->attach($attachment);

        expect($rendersBefore)->toBe(0)
            ->and($wrapper->renders)->toBe(1);
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
