<?php

namespace Renatio\DynamicPDF\Controllers;

use Backend\Behaviors\FormController;
use Backend\Behaviors\ListController;
use Backend\Classes\Controller;
use Backend\Facades\Backend;
use Backend\Facades\BackendAuth;
use Illuminate\Http\RedirectResponse;
use October\Rain\Exception\ApplicationException;
use October\Rain\Exception\ForbiddenException;
use October\Rain\Support\Facades\Flash;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Models\Template;
use Renatio\DynamicPDF\Traits\DetectsViewChanges;
use Renatio\DynamicPDF\Traits\ManagesViewRecords;

class Templates extends Controller
{
    use DetectsViewChanges;
    use ManagesViewRecords;

    protected const LIST_MODELS = ['templates' => Template::class, 'layouts' => Layout::class];

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
        /** Dropped before parent::__construct() so ListController never builds the layouts list. */
        if (! self::canManageLayouts()) {
            unset($this->listConfig['layouts']);
        }

        parent::__construct();

        $this->setSettingsContext();
        $this->addJs('/plugins/renatio/dynamicpdf/assets/js/preview.js?v=1');
        $this->addJs('/plugins/renatio/dynamicpdf/assets/js/variables.js?v=3');
    }

    protected static function canManageLayouts(): bool
    {
        return (bool) BackendAuth::userHasAccess('renatio.dynamicpdf.manage_layouts');
    }

    public function index(?string $tab = null): void
    {
        $this->asExtension('ListController')->index();

        $canManageLayouts = self::canManageLayouts();

        $this->bodyClass = 'compact-container';
        $this->vars['canManageLayouts'] = $canManageLayouts;
        $this->vars['activeTab'] = $canManageLayouts && $tab ? $tab : 'templates';
    }

    public function formBeforeSave(Template $model): void
    {
        if (! $model->exists) {
            $model->is_custom = true;

            return;
        }

        if (! $model->is_custom && $this->postedViewFieldChanged($model, Template::VIEW_FIELDS)) {
            $model->is_custom = true;
        }
    }

    public function index_onDuplicateRecord(): RedirectResponse
    {
        $definition = $this->listDefinition();
        $this->checkListPermission($definition, 'create');
        $copy = $this->findListRecord($definition)->duplicate();

        Flash::success(e(trans('renatio.dynamicpdf::lang.templates.duplicate_success')));

        return Backend::redirect("renatio/dynamicpdf/{$definition}/update/{$copy->id}");
    }

    /**
     * @return array<string, string>
     */
    public function index_onResetRecord(): array
    {
        $definition = $this->listDefinition();
        $this->checkListPermission($definition, 'update');
        $model = $this->findListRecord($definition);

        if (! $model->followsView()) {
            throw new ApplicationException(e(trans('renatio.dynamicpdf::lang.templates.reset_view_only')));
        }

        $model->resetToView();

        Flash::success(e(trans('renatio.dynamicpdf::lang.templates.reset_success')));

        return $this->listRefresh($definition);
    }

    /**
     * @return array<string, string>
     */
    public function index_onDeleteRecord(): array
    {
        $definition = $this->listDefinition();
        $this->checkListPermission($definition, 'delete');
        $model = $this->findListRecord($definition);

        if ($model->followsView()) {
            throw new ApplicationException(e(trans('renatio.dynamicpdf::lang.templates.delete_view_refused')));
        }

        $model->delete();

        Flash::success(e(trans('renatio.dynamicpdf::lang.templates.delete_success')));

        return $this->listRefresh($definition);
    }

    /**
     * @param  Template|Layout  $record
     * @param  string|null  $definition
     * @return string|array<string, bool>|null
     */
    public function listOverrideRecordUrl($record, $definition = null): string|array|null
    {
        $permission = "renatio.dynamicpdf.manage_{$definition}";

        if (BackendAuth::userHasAccess("{$permission}.update")) {
            return null;
        }

        if (BackendAuth::userHasAccess("{$permission}.preview")) {
            return "renatio/dynamicpdf/{$definition}/preview/{$record->id}";
        }

        return ['clickable' => false];
    }

    protected function listDefinition(): string
    {
        $definition = post('definition');

        return is_string($definition) && isset(self::LIST_MODELS[$definition]) ? $definition : 'templates';
    }

    protected function checkListPermission(string $definition, string $action): void
    {
        if (! BackendAuth::userHasAccess("renatio.dynamicpdf.manage_{$definition}.{$action}")) {
            throw new ForbiddenException;
        }
    }

    protected function findListRecord(string $definition): Template|Layout
    {
        if ($definition === 'layouts' && ! self::canManageLayouts()) {
            throw new ForbiddenException;
        }

        $id = (int) post('id');
        $class = self::LIST_MODELS[$definition];
        $model = $class::find($id);

        if (! $model) {
            throw new ApplicationException(e(trans('backend::lang.model.not_found', ['class' => $class, 'id' => $id])));
        }

        return $model;
    }
}
