<?php

namespace Renatio\DynamicPDF\Controllers;

use Backend\Behaviors\FormController;
use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use Illuminate\Http\RedirectResponse;
use October\Rain\Exception\ApplicationException;
use October\Rain\Support\Facades\Flash;
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

    /**
     * @param $recordId
     * @return RedirectResponse
     */
    public function update_onResetDefault($recordId)
    {
        $model = $this->formFindModelObject($recordId);

        $model->fillFromCode();
        $model->save();

        Flash::success(e(trans('backend::lang.form.reset_success')));

        return redirect()->refresh();
    }
}
