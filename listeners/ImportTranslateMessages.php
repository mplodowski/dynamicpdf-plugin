<?php

namespace Renatio\DynamicPDF\Listeners;

use RainLab\Translate\Classes\ThemeScanner;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

class ImportTranslateMessages
{
    public function handle(ThemeScanner $scanner): void
    {
        $contents = Layout::all()->pluck('content_html')
            ->merge(Template::all()->pluck('content_html'));

        $messages = $contents->flatMap(fn (?string $content): array => $scanner->parseContent($content));

        $scanner->importMessages($messages->all());
    }
}
