<?php

namespace Renatio\DynamicPDF\Classes;

use Exception;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

class SyncTemplates
{
    public function handle()
    {
        try {
            $this->checkFontsDir();

            $this->checkPublicDir();

            $this->createLayouts();

            $registeredTemplates = PDFManager::instance()->listRegisteredTemplates();

            if (! $registeredTemplates) {
                return;
            }

            $dbTemplates = Template::lists('is_custom', 'code');

            $this->clearNonCustomizedTemplates($dbTemplates, $registeredTemplates);

            $newTemplates = array_diff_key($registeredTemplates, $dbTemplates);

            $this->createTemplates($newTemplates);
        } catch (Exception $e) {
            //
        }
    }

    protected function createLayouts()
    {
        $registeredLayouts = PDFManager::instance()->listRegisteredLayouts();

        if (! $registeredLayouts) {
            return;
        }

        $dbLayouts = Layout::lists('code', 'code');

        foreach ($registeredLayouts as $code) {
            if (array_key_exists($code, $dbLayouts)) {
                continue;
            }

            $layout = new Layout;
            $layout->code = $code;
            $layout->is_locked = true;
            $layout->fillFromView($code);
            $layout->save();
        }
    }

    protected function clearNonCustomizedTemplates($dbTemplates, $registeredTemplates)
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

    protected function createTemplates($templates)
    {
        foreach ($templates as $code) {
            $template = new Template;
            $template->fillFromView($code);
            $template->forceSave();
        }
    }

    protected function checkFontsDir()
    {
        if (! file_exists(config('dompdf.defines.font_dir'))) {
            mkdir(config('dompdf.defines.font_dir'), 0755, true);
        }
    }

    protected function checkPublicDir()
    {
        if (! file_exists('public')) {
            mkdir('public', 0755, true);
        }
    }
}
