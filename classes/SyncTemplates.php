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
            $this->createLayouts();

            $registeredTemplates = PDFManager::instance()->listRegisteredTemplates();

            if (!$registeredTemplates) {
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

        if (!$registeredLayouts) {
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

            if (!array_key_exists($code, $registeredTemplates)) {
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
}
