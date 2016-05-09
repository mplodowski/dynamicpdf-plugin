<?php namespace Renatio\DynamicPDF\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use Renatio\DynamicPDF\Models\PDFLayout;

/**
 * PDF Layouts Back-end Controller
 */
class PDFLayouts extends Controller
{

    /**
     * @var array Behaviours
     */
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

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

        BackendMenu::setContext('Renatio.DynamicPDF', 'dynamicpdf', 'layouts');
    }
    
    /**
     * Preview PDF layout
     *
     * @param $recordId
     * @return mixed
     */
    public function preview($recordId)
    {
        try
        {
            $model = $this->formFindModelObject($recordId);

            return PDFLayout::render($model->code);

        } catch (Exception $e)
        {
            Flash::error($e->getMessage());
        }
    }
}