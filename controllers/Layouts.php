<?php

namespace Renatio\DynamicPDF\Controllers;

use Backend\Behaviors\FormController;
use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use October\Rain\Exception\ApplicationException;
use Renatio\DynamicPDF\Classes\PDF;
use System\Classes\SettingsManager;

/**
 * Class Layouts
 * @package Renatio\DynamicPDF\Controllers
 */
class Layouts extends Controller
{

    /**
     * @var array
     */
    public $requiredPermissions = ['renatio.dynamicpdf.manage_layouts'];

    /**
     * @var array
     */
    public $implement = [
        FormController::class,
    ];

    /**
     * @var string
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var string
     */
    public $bodyClass = 'compact-container';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Renatio.DynamicPDF', 'templates');
    }

    /**
     * @param $id
     * @return mixed
     */
    public function previewPdf($id)
    {
        $this->pageTitle = trans('renatio.dynamicpdf::lang.templates.preview_pdf');
        $this->bodyClass = null;

        try {
            $model = $this->formFindModelObject($id);
        } catch (ApplicationException $e) {
            return $this->handleError($e);
        }

        return PDF::loadLayout($model->code)
            ->setOptions([
                'logOutputFile' => storage_path('temp/log.htm'),
                'isRemoteEnabled' => true,
            ])
            ->stream();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function preview($id)
    {
        $this->bodyClass = null;

        return $this->asExtension('FormController')->preview($id);
    }

    /**
     * Renders HTML for given layout ID
     *
     * @param $id
     * @return mixed
     */
    public function html($id)
    {
        $model = $this->formFindModelObject($id);

        return response($model->html);
    }
}
