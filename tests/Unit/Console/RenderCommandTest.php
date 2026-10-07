<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

describe('dynamicpdf:render', function () {
    afterEach(fn () => $GLOBALS['_dompdf_warnings'] = []);

    beforeEach(function () {
        $this->directory = $this->temporaryDirectory('render');
        $this->template = $this->createTemplate([
            'code' => 'acme::pdf.letter',
            'content_html' => '<p>Dear {{ name }} from {{ city }}</p>',
            'sample_data' => '{"name": "Ada", "city": "London"}',
        ]);
    });

    it('renders the template to a PDF file and prints its path', function () {
        $path = "{$this->directory}/nested/letter.pdf";

        expect(Artisan::call('dynamicpdf:render', ['code' => 'acme::pdf.letter', '--output' => $path]))->toBe(0)
            ->and(Artisan::output())->toContain($path)
            ->and(File::get($path))->toStartWith('%PDF');
    });

    it('renders the sample data with --data keys overriding it', function () {
        $path = "{$this->directory}/letter.html";
        File::put("{$this->directory}/data.json", '{"name": "Grace"}');

        Artisan::call('dynamicpdf:render', ['code' => 'acme::pdf.letter', '--sample' => true, '--data' => "@{$this->directory}/data.json", '--html' => true, '--output' => $path]);

        expect(File::get($path))->toContain('Dear Grace from London');
    });

    it('prints the dompdf warnings of this render only', function () {
        $GLOBALS['_dompdf_warnings'] = ['left over from an earlier render'];
        DB::table($this->template->getTable())->where('id', $this->template->id)->update(['content_html' => '<p style="bogus-property: 1">x</p>']);

        expect(Artisan::call('dynamicpdf:render', ['code' => 'acme::pdf.letter', '--output' => "{$this->directory}/letter.pdf"]))->toBe(0)
            ->and(Artisan::output())->toContain("'bogus_property' is not a recognized CSS property.")->not->toContain('left over');
    });

    it('fails with a readable message', function (array $options, string $message) {
        $path = "{$this->directory}/failed.pdf";

        expect(Artisan::call('dynamicpdf:render', ['--output' => $path, ...$options]))->toBe(1)
            ->and(Artisan::output())->toContain($message)
            ->and($path)->not->toBeFile();
    })->with([
        'unknown template' => [['code' => 'acme::pdf.nowhere'], 'The template acme::pdf.nowhere does not exist.'],
        'invalid JSON' => [['code' => 'acme::pdf.letter', '--data' => '{"name": '], 'The --data value is not valid JSON'],
        'JSON array' => [['code' => 'acme::pdf.letter', '--data' => '[]'], 'The --data value must be a JSON object.'],
        'unknown layout' => [['code' => 'acme::pdf.letter', '--layout' => 'acme::pdf.layouts.nowhere'], 'The layout acme::pdf.layouts.nowhere does not exist.'],
        'unwritable output' => [['code' => 'acme::pdf.letter', '--output' => '/'], 'ErrorException'],
        'missing data file' => [['code' => 'acme::pdf.letter', '--data' => '@/nowhere/data.json'], 'The data file /nowhere/data.json does not exist'],
    ]);

    it('fails with the Twig error of a broken template', function () {
        DB::table($this->template->getTable())->where('id', $this->template->id)->update(['content_html' => '<p>{{ name </p>']);

        expect(Artisan::call('dynamicpdf:render', ['code' => 'acme::pdf.letter', '--output' => "{$this->directory}/broken.pdf"]))->toBe(1)
            ->and(Artisan::output())->toContain('SyntaxError')->toContain('line 1');
    });
});
