<?php

namespace Renatio\DynamicPDF\Controllers;

use Backend\Behaviors\FormController;
use Backend\Classes\Controller;
use Backend\Facades\Backend;
use Illuminate\Http\RedirectResponse;
use Renatio\DynamicPDF\Models\Layout;
use Renatio\DynamicPDF\Traits\DetectsViewChanges;
use Renatio\DynamicPDF\Traits\ManagesViewRecords;

class Layouts extends Controller
{
    use DetectsViewChanges;
    use ManagesViewRecords;

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

        $this->addViewPath($this->guessViewPathFrom(Templates::class), true);

        $this->setSettingsContext();
        $this->addJs('/plugins/renatio/dynamicpdf/assets/js/preview.js?v=1');
    }

    public function index(): RedirectResponse
    {
        return Backend::redirect('renatio/dynamicpdf/templates/index/layouts');
    }

    public function formBeforeSave(Layout $model): void
    {
        if ($model->is_locked && $this->postedViewFieldChanged($model, Layout::VIEW_FIELDS)) {
            $model->is_locked = false;
        }
    }
}
