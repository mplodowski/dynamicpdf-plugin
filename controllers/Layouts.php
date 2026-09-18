<?php

namespace Renatio\DynamicPDF\Controllers;

use Backend\Behaviors\FormController;
use Backend\Classes\Controller;
use Backend\Facades\Backend;
use Backend\Facades\BackendMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use October\Rain\Exception\ApplicationException;
use October\Rain\Support\Facades\Flash;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\SyncTemplates;
use Renatio\DynamicPDF\Traits\ChecksFormPermissions;
use System\Classes\SettingsManager;

class Layouts extends Controller
{
    use ChecksFormPermissions;

    /** @var array<int, string> */
    public $requiredPermissions = ['renatio.dynamicpdf.manage_layouts'];

    /** @var array<int, class-string> */
    public $implement = [
        FormController::class,
    ];

    /** @var string */
    public $formConfig = 'config_form.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Renatio.DynamicPDF', 'templates');
    }

    public function beforeDisplay(): void
    {
        (new SyncTemplates)->handle();
    }

    public function previewpdf(int|string $id): ?Response
    {
        $this->requireFormPermission('modelPreview');

        $this->pageTitle = e(trans('renatio.dynamicpdf::lang.templates.preview_pdf'));

        try {
            $model = $this->formFindModelObject($id);
        } catch (ApplicationException $e) {
            $this->handleError($e);

            return null;
        }

        $pdf = PDF::loadLayout($model->code)
            ->setLogOutputFile(config('app.debug') ? storage_path('temp/log.htm') : '')
            ->allowRemoteApplicationAssets()
            ->setDpi(300);

        $pdf->render();

        return $pdf->addInfo(['Title' => $model->name])->stream(Str::slug($model->name) . '.pdf');
    }

    public function html(int|string $id): Response
    {
        $this->requireFormPermission('modelPreview');

        $model = $this->formFindModelObject($id);

        return response($model->html)->header('Content-Security-Policy', "sandbox; script-src 'none'; object-src 'none'");
    }

    public function update_onDuplicate(int|string $recordId): RedirectResponse
    {
        $this->requireFormPermission('modelCreate');

        $copy = $this->formFindModelObject($recordId)->duplicate();

        Flash::success(e(trans('renatio.dynamicpdf::lang.templates.duplicate_success')));

        return Backend::redirect('renatio/dynamicpdf/layouts/update/' . $copy->id);
    }

    public function update_onResetDefault(int|string $recordId): RedirectResponse
    {
        $this->requireFormPermission('modelUpdate');

        $this->formFindModelObject($recordId)->resetToView();

        Flash::success(e(trans('renatio.dynamicpdf::lang.templates.reset_success')));

        return redirect()->refresh();
    }
}
