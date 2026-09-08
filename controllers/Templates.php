<?php

namespace Renatio\DynamicPDF\Controllers;

use Backend\Behaviors\FormController;
use Backend\Behaviors\ListController;
use Backend\Classes\Controller;
use Backend\Facades\Backend;
use Backend\Facades\BackendMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use October\Rain\Exception\ApplicationException;
use October\Rain\Support\Facades\Flash;
use Renatio\DynamicPDF\Classes\PDF;
use Renatio\DynamicPDF\Classes\SyncTemplates;
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

    public function beforeDisplay(): void
    {
        (new SyncTemplates)->handle();
    }

    public function index(?string $tab = null): void
    {
        $this->asExtension('ListController')->index();

        $this->bodyClass = 'compact-container';
        $this->vars['activeTab'] = $tab ?: 'templates';
    }

    /**
     * Only a change to what the view file provides detaches the template from the view;
     * editing the sample data alone keeps it view-driven. The posted values are compared
     * with the model because the form data is applied to it only after this hook.
     */
    public function formBeforeSave(Template $model): void
    {
        if ($model->is_custom) {
            return;
        }

        $posted = $this->formGetWidget()->getSaveData();
        $fields = ['title' => 'title', 'description' => 'description', 'content_html' => 'content_html', 'layout' => 'layout_id', 'size' => 'size', 'orientation' => 'orientation'];

        foreach ($fields as $field => $attribute) {
            if (array_key_exists($field, $posted) && $this->normalize($posted[$field]) !== $this->normalize($model->getAttribute($attribute))) {
                $model->is_custom = true;

                return;
            }
        }
    }

    protected function normalize(mixed $value): string
    {
        return str_replace("\r\n", "\n", trim((string) $value));
    }

    public function previewpdf(int|string $id): ?Response
    {
        $this->pageTitle = e(trans('renatio.dynamicpdf::lang.templates.preview_pdf'));

        try {
            $model = $this->formFindModelObject($id);
        } catch (ApplicationException $e) {
            $this->handleError($e);

            return null;
        }

        return PDF::loadTemplate($model->code, $model->sampleData())
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

    public function update_onDuplicate(int|string $recordId): RedirectResponse
    {
        $copy = $this->formFindModelObject($recordId)->duplicate();

        Flash::success(e(trans('renatio.dynamicpdf::lang.templates.duplicate_success')));

        return Backend::redirect('renatio/dynamicpdf/templates/update/' . $copy->id);
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
