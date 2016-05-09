<?php namespace Renatio\DynamicPDF\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use Exception;
use Renatio\DynamicPDF\Models\PDFTemplate;
use Flash;

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
    public $listConfig = ['config_list.yaml'];

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Renatio.DynamicPDF', 'dynamicpdf', 'pdftemplates');
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

        } catch (Exception $e)
        {
            Flash::error($e->getMessage());
        }
    }
}