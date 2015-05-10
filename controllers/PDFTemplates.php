<?php namespace Renatio\DynamicPDF\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use Exception;
use Renatio\DynamicPDF\Models\PDFTemplate;
use System\Classes\SettingsManager;

/**
 * PDF Templates Back-end Controller
 */
class PDFTemplates extends Controller
{

    /**
     * @var array Behaviours
     */
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    /**
     * @var array Permissions
     */
    public $requiredPermissions = ['renatio.dynamicpdf.manage_pdf_templates'];

    /**
     * @var string Form config
     */
    public $formConfig = 'config_form.yaml';

    /**
     * @var array List config
     */
    public $listConfig = [
        'templates' => 'config_templates_list.yaml',
        'layouts'   => 'config_layouts_list.yaml'
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Renatio.DynamicPDF', 'pdftemplates');
    }

    /**
     * List PDF templates
     */
    public function index()
    {
        $this->asExtension('ListController')->index();
        $this->bodyClass = 'compact-container';
    }

    /**
     * Preview PDF template
     *
     * @param $recordId
     * @return mixed
     */
    public function preview($recordId)
    {
        try
        {
            $model = $this->formFindModelObject($recordId);

            return PDFTemplate::render($model->code);

        } catch (Exception $ex)
        {
            Flash::error($ex->getMessage());
        }
    }
}