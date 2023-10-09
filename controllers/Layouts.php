<?php

namespace Renatio\DynamicPDF\Controllers;

use Backend\Behaviors\FormController;
use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use October\Rain\Exception\ApplicationException;
use October\Rain\Support\Facades\Flash;
use Renatio\DynamicPDF\Classes\PDF;
use System\Classes\SettingsManager;

class Layouts extends Controller
{
    public $requiredPermissions = ['renatio.dynamicpdf.manage_layouts'];

    public $implement = [
        FormController::class,
    ];

    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Renatio.DynamicPDF', 'templates');
    }

    public function previewPdf($id)
    {
        $this->pageTitle = e(trans('renatio.dynamicpdf::lang.templates.preview_pdf'));

        try {
            $model = $this->formFindModelObject($id);
        } catch (ApplicationException $e) {
            return $this->handleError($e);
        }

        return PDF::loadLayout($model->code)
            ->setLogOutputFile(storage_path('temp/log.htm'))
            ->setIsRemoteEnabled(true)
            ->setDpi(300)
            ->setIsPhpEnabled(! config('cms.safe_mode'))
            ->stream();
    }

    public function html($id)
    {
        $model = $this->formFindModelObject($id);

        return response($model->html);
    }

    public function update_onResetDefault($recordId)
    {
        $model = $this->formFindModelObject($recordId);

        $model->fillFromCode();
        $model->save();

        Flash::success(e(trans('backend::lang.form.reset_success')));

        return redirect()->refresh();
    }
}
