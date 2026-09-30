<?php

namespace Renatio\DynamicPDF\Listeners;

use RainLab\Translate\Classes\ThemeScanner;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

class ImportTranslateMessages
{
    /**
     * Models are hydrated rather than plucked: fetching promotes the active site's translation,
     * and a template that follows its view reads the markup from the file, not the stale column.
     */
    public function handle(ThemeScanner $scanner): void
    {
        $contents = Layout::all()->pluck('content_html')
            ->merge(Template::all()->pluck('content_html'));

        $messages = $contents->flatMap(fn (?string $content): array => $scanner->parseContent($content));

        $scanner->importMessages($messages->all());
    }
}
