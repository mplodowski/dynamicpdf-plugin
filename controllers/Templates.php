<?php

namespace Renatio\DynamicPDF\Controllers;

use Backend\Behaviors\FormController;
use Backend\Behaviors\ListController;
use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use October\Rain\Exception\ApplicationException;
use October\Rain\Support\Facades\Flash;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Models\Template;
use System\Classes\SettingsManager;

class Templates extends Controller
{
    /** @var array<int, string> */
    public $requiredPermissions = ['renatio.dynamicpdf.manage_templates'];

    /** @var array<int, class-string> */
    public $implement = [
        ListController::class,
        FormController::class,
    ];

    /** @var array<string, string> */
    public $listConfig = [
        'templates' => 'config_templates_list.yaml',
        'layouts' => 'config_layouts_list.yaml',
    ];

    /** @var string */
    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Renatio.DynamicPDF', 'templates');
    }

    public function index(?string $tab = null): void
    {
        $this->asExtension('ListController')->index();

        $this->bodyClass = 'compact-container';
        $this->vars['activeTab'] = $tab ?: 'templates';
    }

    public function formBeforeSave(Template $model): void
    {
        $model->is_custom = true;
    }

    public function previewPdf(int|string $id): ?Response
    {
        $this->pageTitle = e(trans('renatio.dynamicpdf::lang.templates.preview_pdf'));

        try {
            $model = $this->formFindModelObject($id);
        } catch (ApplicationException $e) {
            $this->handleError($e);

            return null;
        }

        return PDF::loadTemplate($model->code)
            ->setLogOutputFile(storage_path('temp/log.htm'))
            ->allowRemoteApplicationAssets()
            ->setDpi(300)
            ->stream();
    }

    public function html(int|string $id): Response
    {
        $model = $this->formFindModelObject($id);

        return response($model->html)->header('Content-Security-Policy', 'sandbox allow-same-origin');
    }

    public function update_onResetDefault(int|string $recordId): RedirectResponse
    {
        $model = $this->formFindModelObject($recordId);

        $model->fillFromCode();
        $model->is_custom = false;
        $model->save();

        Flash::success(e(trans('backend::lang.form.reset_success')));

        return redirect()->refresh();
    }
}
