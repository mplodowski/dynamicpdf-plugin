<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Support\Facades\Log;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Throwable;

class SyncTemplates
{
    public function handle(): void
    {
        $this->checkFontsDir();
        $this->createLayouts();

        $registeredTemplates = PDFManager::instance()->listRegisteredTemplates();

        if (! $registeredTemplates) {
            return;
        }

        $dbTemplates = Template::query()->pluck('is_custom', 'code')->all();

        $this->clearNonCustomizedTemplates($dbTemplates, $registeredTemplates);
        $this->createTemplates(array_diff_key($registeredTemplates, $dbTemplates));
    }

    protected function checkFontsDir(): void
    {
        $fontDir = config('dompdf.options.font_dir');

        if (! $fontDir || file_exists($fontDir)) {
            return;
        }

        if (! @mkdir($fontDir, 0755, true) && ! is_dir($fontDir)) {
            Log::error("Renatio.DynamicPDF could not create the dompdf font directory {$fontDir}.");
        }
    }

    protected function createLayouts(): void
    {
        $registeredLayouts = PDFManager::instance()->listRegisteredLayouts();

        if (! $registeredLayouts) {
            return;
        }

        $dbLayouts = Layout::query()->pluck('code', 'code')->all();

        foreach (array_diff_key($registeredLayouts, $dbLayouts) as $code) {
            $this->create($code, function () use ($code): void {
                $layout = new Layout;
                $layout->is_locked = true;
                $layout->fillFromView($code);
                $layout->save();
            });
        }
    }

    /**
     * @param  array<string, bool>  $dbTemplates
     * @param  array<string, string>  $registeredTemplates
     */
    protected function clearNonCustomizedTemplates(array $dbTemplates, array $registeredTemplates): void
    {
        foreach ($dbTemplates as $code => $isCustom) {
            if (! $isCustom && ! array_key_exists($code, $registeredTemplates)) {
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
            $this->create($code, function () use ($code): void {
                $template = new Template;
                $template->fillFromView($code);
                $template->forceSave();
            });
        }
    }

    /**
     * One registered code without a view file must not stop the others from syncing.
     */
    protected function create(string $code, callable $create): void
    {
        try {
            $create();
        } catch (Throwable $e) {
            Log::error("Renatio.DynamicPDF could not sync {$code}: {$e->getMessage()}");
        }
    }
}
