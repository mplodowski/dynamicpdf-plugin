<?php

namespace Renatio\DynamicPDF\Listeners;

use RainLab\Translate\Classes\ThemeScanner;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

class ImportTranslateMessages
{
    /**
     * Templates are hydrated rather than plucked: a template that follows its view reads the
     * markup from the view file, and the stored column lags behind it until the next sync.
     */
    public function handle(ThemeScanner $scanner): void
    {
        $contents = Layout::query()->pluck('content_html')
            ->merge(Template::all()->pluck('content_html'));

        $messages = $contents->flatMap(fn (?string $content): array => $scanner->parseContent($content));

        $scanner->importMessages($messages->all());
    }
}
