<?php

use October\Rain\Exception\ValidationException;
use Renatio\DynamicPDF\Controllers\Layouts;
use Renatio\DynamicPDF\Controllers\Templates;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

describe('Backend preview tools', function () {
    it('renders the HTML preview with the sample data of the template', function () {
        $template = $this->createTemplate(['content_html' => '<p>Hello {{ name }}</p>', 'sample_data' => '{"name": "Jane"}']);

        expect((new Templates)->html($template->id)->getContent())->toContain('<p>Hello Jane</p>');
    });

    it('rejects sample data that is not JSON', function () {
        expect(fn () => $this->createTemplate(['sample_data' => '{name: Jane']))->toThrow(ValidationException::class);
    });

    it('duplicates a template with a unique code, the same layout and a customised flag', function () {
        $layout = $this->createLayout();
        $template = $this->createTemplate(['code' => 'acme::pdf.invoice', 'layout_id' => $layout->id, 'is_custom' => false, 'content_html' => '<p>{{ x }}</p>']);
        $this->createTemplate(['code' => 'acme::pdf.invoice_copy']);

        $response = (new Templates)->update_onDuplicate($template->id);
        $copy = Template::whereCode('acme::pdf.invoice_copy2')->firstOrFail();

        expect($copy->getAttribute('is_custom'))->toBeTruthy()
            ->and((int) $copy->getAttribute('layout_id'))->toBe($layout->id)
            ->and((string) $copy->getAttribute('content_html'))->toBe('<p>{{ x }}</p>')
            ->and($response->getTargetUrl())->toContain('templates/update/' . $copy->id);
    });

    it('duplicates a layout as an unlocked copy', function () {
        $layout = $this->createLayout(['code' => 'acme::pdf.layouts.default', 'is_locked' => true]);

        (new Layouts)->update_onDuplicate($layout->id);

        expect(Layout::whereCode('acme::pdf.layouts.default_copy')->firstOrFail()->getAttribute('is_locked'))->toBeFalsy();
    });
});
