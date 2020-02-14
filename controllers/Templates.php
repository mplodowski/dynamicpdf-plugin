<?php

namespace Renatio\DynamicPDF\Controllers;

use Backend\Behaviors\FormController;
use Backend\Behaviors\ListController;
use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use October\Rain\Exception\ApplicationException;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use System\Classes\SettingsManager;

/**
 * Class Templates
 * @package Renatio\DynamicPDF\Controllers
 */
class Templates extends Controller
{

    /**
     * @var array
     */
    public $requiredPermissions = ['renatio.dynamicpdf.manage_templates'];

    /**
     * @var array
     */
    public $implement = [
        ListController::class,
        FormController::class,
    ];

    /**
     * @var array
     */
    public $listConfig = [
        'templates' => 'config_templates_list.yaml',
        'layouts' => 'config_layouts_list.yaml',
    ];

    /**
     * @var string
     */
    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Renatio.DynamicPDF', 'templates');
    }

    /**
     * @param  null  $tab
     * @throws FileNotFoundException
     */
    public function index($tab = null)
    {
        (new SyncTemplates)->handle();

        $this->asExtension('ListController')->index();

        $this->bodyClass = 'compact-container';
        $this->vars['activeTab'] = $tab ?: 'templates';
    }

    /**
     * @param $model
     */
    public function formBeforeSave($model)
    {
        $model->is_custom = 1;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function previewPdf($id)
    {
        $this->pageTitle = trans('renatio.dynamicpdf::lang.templates.preview_pdf');

        try {
            $model = $this->formFindModelObject($id);
        } catch (ApplicationException $e) {
            return $this->handleError($e);
        }

        return PDF::loadTemplate($model->code)
            ->setOptions([
                'logOutputFile' => storage_path('temp/log.htm'),
                'isRemoteEnabled' => true,
            ])
            ->stream();
    }

    /**
     * Renders HTML for given template ID
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
