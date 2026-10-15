<?php

use Backend\Facades\Backend;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Renatio\DynamicPDF\Classes\Events;

/**
 * @param  TestResponse<\Illuminate\Http\Response>  $response
 */
function previewFrame(TestResponse $response): DOMElement
{
    $document = new DOMDocument;
    @$document->loadHTML((string) $response->json('result'));

    $frame = $document->getElementsByTagName('iframe')->item(0);
    expect($frame)->toBeInstanceOf(DOMElement::class);

    return $frame;
}

describe('Preview of unsaved changes', function () {
    afterEach(fn () => Event::forget(Events::AFTER_RENDER));

    it('renders the posted template in a fully sandboxed srcdoc frame without saving it', function () {
        actingAsPdfManager();
        $template = $this->createTemplate(['content_html' => '<p>Saved</p>']);

        $response = $this->previewUnsaved('templates', $template->id, [
            'content_html' => '<p>Hello {{ name }}</p><script>alert(1)</script>',
            'sample_data' => '{"name": "Jane"}',
        ])->assertOk();

        $frame = previewFrame($response);

        expect($frame->hasAttribute('sandbox'))->toBeTrue()
            ->and($frame->getAttribute('sandbox'))->toBe('')
            ->and($frame->getAttribute('srcdoc'))->toContain('<p>Hello Jane</p>')
            ->and($this->findTemplate($template->code)->content_html)->toBe('<p>Saved</p>');
    });

    it('uses the posted layout without changing the stored one', function () {
        actingAsPdfManager();
        $stored = $this->createLayout(['content_html' => '<html><body>STORED {{ content_html|raw }}</body></html>']);
        $posted = $this->createLayout(['content_html' => '<html><body>POSTED {{ content_html|raw }}</body></html>']);
        $template = $this->createTemplate(['layout_id' => $stored->id]);

        $srcdoc = previewFrame($this->previewUnsaved('templates', $template->id, ['layout' => $posted->id]))->getAttribute('srcdoc');

        expect($srcdoc)->toContain('POSTED')
            ->and($srcdoc)->not->toContain('STORED')
            ->and($this->findTemplate($template->code)->layout_id)->toBe($stored->id);
    });

    it('builds the PDF from the posted content', function () {
        actingAsPdfManager();
        $template = $this->createTemplate(['content_html' => '<p>Saved</p>']);
        $rendered = [];
        Event::listen(Events::AFTER_RENDER, function ($pdf, $model, string $html) use (&$rendered): void {
            $rendered[] = $html;
        });

        $pdf = base64_decode(previewFrame($this->previewUnsaved('templates', $template->id, ['content_html' => '<p>Unsaved PDF</p>'], 'pdf'))->getAttribute('data-pdf'));

        expect($pdf)->toStartWith('%PDF')
            ->and($rendered)->toBe(['<p>Unsaved PDF</p>']);
    });

    it('previews the posted layout markup and CSS', function () {
        actingAsPdfManager();
        $layout = $this->createLayout(['content_html' => '<html><body>Saved</body></html>']);

        $srcdoc = previewFrame($this->previewUnsaved('layouts', $layout->id, [
            'content_html' => '<html><head><style>{{ css|raw }}</style></head><body>Unsaved layout</body></html>',
            'content_css' => 'body { color: #123456; }',
        ]))->getAttribute('srcdoc');

        expect($srcdoc)->toContain('Unsaved layout')
            ->and($srcdoc)->toContain('#123456')
            ->and($this->findLayout($layout->code)->content_html)->toBe('<html><body>Saved</body></html>');
    });

    it('answers invalid Twig with the preview error instead of failing', function (string $mode) {
        actingAsPdfManager();
        $template = $this->createTemplate();

        $response = $this->previewUnsaved('templates', $template->id, ['content_html' => '<p>{{ broken }</p>'], $mode);

        expect($response->status())->toBe(400)
            ->and($response->json('__ajax.message'))->toStartWith('The preview could not be rendered.');
    })->with(['html', 'pdf']);

    it('requires the preview permission', function (string $definition) {
        actingAsPdfManager(except: ["manage_{$definition}.preview"]);
        $record = $definition === 'templates' ? $this->createTemplate() : $this->createLayout();
        $rendered = false;
        Event::listen(Events::AFTER_RENDER, function () use (&$rendered): void {
            $rendered = true;
        });

        $response = $this->previewUnsaved($definition, $record->id, ['content_html' => '<p>Unsaved</p>']);

        expect($response->json('__ajax.message'))->toBe('Access Denied')
            ->and($response->json('result'))->toBeNull()
            ->and($rendered)->toBeFalse();
    })->with(['templates', 'layouts']);

    it('refuses posted markup from a role that may only preview saved records', function (string $definition, string $action) {
        actingAsPdfManager(except: ["manage_{$definition}.update"]);
        $record = $definition === 'templates' ? $this->createTemplate() : $this->createLayout();
        $rendered = false;
        Event::listen(Events::AFTER_RENDER, function () use (&$rendered): void {
            $rendered = true;
        });
        $model = $definition === 'templates' ? 'Template' : 'Layout';

        $response = $this->post(Backend::url("renatio/dynamicpdf/{$definition}/{$action}/{$record->id}"), [$model => ['content_html' => '<p>{{ 7 * 7 }}</p>'], 'mode' => 'html'], [
            'X-AJAX-HANDLER' => 'onPreviewUnsaved',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        expect($response->json('result'))->toBeNull()
            ->and($rendered)->toBeFalse();
    })->with(['templates', 'layouts'])->with(['update', 'preview']);

    it('previews a template that is being created', function () {
        actingAsPdfManager();

        $response = $this->post(Backend::url('renatio/dynamicpdf/templates/create'), ['Template' => ['title' => 'New', 'content_html' => '<p>Brand new</p>'], 'mode' => 'html'], [
            'X-AJAX-HANDLER' => 'onPreviewUnsaved',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk();

        expect(previewFrame($response)->getAttribute('srcdoc'))->toContain('<p>Brand new</p>');
    });

    it('reports invalid sample data instead of rendering without it', function () {
        actingAsPdfManager();
        $template = $this->createTemplate();

        $response = $this->previewUnsaved('templates', $template->id, ['content_html' => '<p>{{ name }}</p>', 'sample_data' => '{"name": "Jane",}']);

        expect($response->json('result'))->toBeNull()
            ->and((string) $response->getContent())->toContain('sample');
    });
});
