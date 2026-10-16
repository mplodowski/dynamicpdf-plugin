<?php

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Renatio\DynamicPDF\Classes\TwigRenderer;

describe('Sandbox warnings', function () {
    beforeEach(function () {
        $this->activateTheme(null);
        $this->warnings = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event): void {
            if ($event->level === 'warning') {
                $this->warnings[] = $event->message;
            }
        });
    });

    it('logs a model method or relation that the sandbox turned into null', function () {
        $layout = $this->createLayout(['name' => 'Framed']);
        $template = $this->createTemplate(['layout_id' => $layout->id])->fresh();

        $output = (new TwigRenderer)->render('[{{ invoice.layout.name }}|{{ invoice.getKey() }}]', ['invoice' => $template], 'acme::pdf.invoice');

        expect($output)->toBe('[|]')
            ->and($this->warnings)->toHaveCount(2)->sequence(
                fn ($warning) => $warning->toContain('acme::pdf.invoice')->toContain('"layout"')->toContain('relation'),
                fn ($warning) => $warning->toContain('"getKey"')->toContain('method'),
            );
    });

    it('logs nothing for missing keys of plain data or values tested for', function () {
        $template = $this->createTemplate()->fresh();

        (new TwigRenderer)->render(
            '{{ row.layout }}{{ invoice.layout.name ?? "none" }}{{ invoice.layout is defined ? "yes" }}{{ invoice.layout|default("x") }}',
            ['row' => ['title' => 'Plain'], 'invoice' => $template],
            'acme::pdf.invoice',
        );

        expect($this->warnings)->toBe([]);
    });
});
