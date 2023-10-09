<?php

namespace Renatio\DynamicPDF\Controllers;

use Backend\Behaviors\FormController;
use Backend\Behaviors\ListController;
use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use October\Rain\Exception\ApplicationException;
use October\Rain\Support\Facades\Flash;
use Renatio\DynamicPDF\Classes\PDF;
use System\Classes\SettingsManager;

class Templates extends Controller
{
    public $requiredPermissions = ['renatio.dynamicpdf.manage_templates'];

    public $implement = [
        ListController::class,
        FormController::class,
    ];

    public $listConfig = [
        'templates' => 'config_templates_list.yaml',
        'layouts' => 'config_layouts_list.yaml',
    ];

    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Renatio.DynamicPDF', 'templates');
    }

    public function index($tab = null)
    {
        $this->asExtension('ListController')->index();

        $this->bodyClass = 'compact-container';
        $this->vars['activeTab'] = $tab ?: 'templates';
    }

    public function formBeforeSave($model)
    {
        $model->is_custom = 1;
    }

    public function previewPdf($id)
    {
        $this->pageTitle = e(trans('renatio.dynamicpdf::lang.templates.preview_pdf'));

        try {
            $model = $this->formFindModelObject($id);
        } catch (ApplicationException $e) {
            return $this->handleError($e);
        }

        return PDF::loadTemplate($model->code)
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
        $model->is_custom = 0;
        $model->save();

        Flash::success(e(trans('backend::lang.form.reset_success')));

        return redirect()->refresh();
    }
}
