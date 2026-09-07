<?php

namespace Renatio\DynamicPDF\Classes;

use Exception;
use October\Rain\Support\Facades\Event;
use RainLab\Translate\Classes\ThemeScanner;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

class SyncTemplates
{
    public function handle(): void
    {
        try {
            $this->checkFontsDir();
            $this->createLayouts();

            $registeredTemplates = PDFManager::instance()->listRegisteredTemplates();

            if (! $registeredTemplates) {
                return;
            }

            $dbTemplates = Template::query()->pluck('is_custom', 'code')->all();

            $this->clearNonCustomizedTemplates($dbTemplates, $registeredTemplates);

            $newTemplates = array_diff_key($registeredTemplates, $dbTemplates);

            $this->createTemplates($newTemplates);
            $this->scanTranslatedMessages();
        } catch (Exception) {
        }
    }

    protected function checkFontsDir(): void
    {
        $fontDir = config('dompdf.options.font_dir');

        if ($fontDir && ! file_exists($fontDir)) {
            mkdir($fontDir, 0755, true);
        }
    }

    protected function createLayouts(): void
    {
        $registeredLayouts = PDFManager::instance()->listRegisteredLayouts();

        if (! $registeredLayouts) {
            return;
        }

        $dbLayouts = Layout::query()->pluck('code', 'code')->all();

        foreach ($registeredLayouts as $code) {
            if (array_key_exists($code, $dbLayouts)) {
                continue;
            }

            $layout = new Layout;
            $layout->is_locked = true;
            $layout->fillFromView($code);
            $layout->save();
        }
    }

    /**
     * @param  array<string, bool>  $dbTemplates
     * @param  array<string, string>  $registeredTemplates
     */
    protected function clearNonCustomizedTemplates(array $dbTemplates, array $registeredTemplates): void
    {
        foreach ($dbTemplates as $code => $isCustom) {
            if ($isCustom) {
                continue;
            }

            if (! array_key_exists($code, $registeredTemplates)) {
                Template::whereCode($code)->delete();
            }
        }
    }

    /**
     * @param  array<string, string>  $templates
     */
    protected function createTemplates(array $templates): void
    {
        foreach ($templates as $code) {
            $template = new Template;
            $template->fillFromView($code);
            $template->forceSave();
        }
    }

    protected function scanTranslatedMessages(): void
    {
        Event::listen('rainlab.translate.themeScanner.afterScan', function (ThemeScanner $scanner): void {
            $messages = [];

            foreach (Layout::all() as $layout) {
                $messages = array_merge($messages, $scanner->parseContent($layout->content_html));
            }

            foreach (Template::all() as $template) {
                $messages = array_merge($messages, $scanner->parseContent($template->content_html));
            }

            $scanner->importMessages($messages);
        });
    }
}
