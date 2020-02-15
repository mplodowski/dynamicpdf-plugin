<?php

namespace Renatio\DynamicPDF\Classes;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;

/**
 * Class SyncTemplates
 * @package Renatio\DynamicPDF\Classes
 */
class SyncTemplates
{

    /**
     * Synchronize registered PDF templates
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function handle()
    {
        $this->createLayouts();

        $registeredTemplates = PDFManager::instance()->listRegisteredTemplates();

        if (!$registeredTemplates) {
            return;
        }

        $dbTemplates = Template::lists('is_custom', 'code');

        $this->clearNonCustomizedTemplates($dbTemplates, $registeredTemplates);

        $newTemplates = array_diff_key($registeredTemplates, $dbTemplates);

        $this->createTemplates($newTemplates);
    }

    /**
     * @return void
     * @throws FileNotFoundException
     */
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

    /**
     * @param $dbTemplates
     * @param $registeredTemplates
     */
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

    /**
     * @param $templates
     * @throws FileNotFoundException
     */
    protected function createTemplates($templates)
    {
        foreach ($templates as $code) {
            $template = new Template;
            $template->fillFromView($code);
            $template->forceSave();
        }
    }
}
