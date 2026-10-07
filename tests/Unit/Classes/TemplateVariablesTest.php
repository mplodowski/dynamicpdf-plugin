<?php

use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Classes\TemplateVariables;
use Renatio\DynamicPDF\Classes\TwigRenderer;
use Renatio\DynamicPDF\Models\Template;

/**
 * @param  list<array{label: string, snippet: string, description: string|null}>|null  $entries
 * @return array<string, string>
 */
function snippetsByLabel(?array $entries): array
{
    return array_column($entries ?? [], 'snippet', 'label');
}

describe('Template variables', function () {
    it('flattens sample data into keys with loop snippets for arrays', function () {
        $template = new Template(['sample_data' => json_encode([
            'order' => ['number' => 'FV/1', 'customer' => ['name' => 'Jan']],
            'items' => [['title' => 'Pen', 'qty' => 1], ['title' => 'Ink', 'price' => 9]],
            'tags' => ['new', 'sale'],
            'orders' => [['lines' => [['qty' => 2]]]],
            'first name' => 'Jan',
            'mixed' => [1, ['a' => 2]],
        ])]);

        expect(snippetsByLabel((new TemplateVariables)->forTemplate($template)['sample']))->toBe([
            'order.number' => '{{ order.number }}',
            'order.customer.name' => '{{ order.customer.name }}',
            'items[].title' => '{% for item in items %}{{ item.title }}{% endfor %}',
            'items[].qty' => '{% for item in items %}{{ item.qty }}{% endfor %}',
            'items[].price' => '{% for item in items %}{{ item.price }}{% endfor %}',
            'tags[]' => '{% for tag in tags %}{{ tag }}{% endfor %}',
            'orders[].lines[].qty' => '{% for order_item in orders %}{% for line in order_item.lines %}{{ line.qty }}{% endfor %}{% endfor %}',
            'first name' => "{{ _context['first name'] }}",
            'mixed[]' => '{% for mixed_item in mixed %}{{ mixed_item }}{% endfor %}',
            'mixed[].a' => '{% for mixed_item in mixed %}{{ mixed_item.a }}{% endfor %}',
        ]);
    });

    it('gives snippets that render the sample value, whatever the keys are named', function () {
        PDFManager::instance()->registerVariables(['tag' => 'global']);
        $json = '{"0": "zero", "true": "yes", "meta": {}, "loops": [1], "ins": [{"in": "x"}], "tags": ["a"]}';
        $template = new Template(['sample_data' => $json]);

        $rendered = array_map(
            fn (string $snippet): string => (new TwigRenderer)->render($snippet, $template->sampleData(), 'snippet'),
            snippetsByLabel((new TemplateVariables)->forTemplate($template)['sample']),
        );

        expect($rendered)->toBe([
            '0' => 'zero',
            'true' => 'yes',
            'loops[]' => '1',
            'ins[].in' => 'x',
            'tags[]' => 'a',
        ]);
    });

    it('lists registered global variables without resolving closures', function () {
        PDFManager::instance()->registerVariables([
            'company' => ['name' => 'Acme'],
            'vat_rate' => fn () => throw new RuntimeException('resolved'),
            'css' => 'reserved',
        ]);

        expect(snippetsByLabel((new TemplateVariables)->forTemplate(new Template)['globals']))
            ->toMatchArray(['company.name' => '{{ company.name }}', 'vat_rate' => '{{ vat_rate }}', 'locale' => '{{ locale }}'])
            ->not->toHaveKey('css');
    });
});
