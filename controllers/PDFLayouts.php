<?php namespace Renatio\DynamicPDF\Controllers;

use Backend\Classes\Controller;
use Backend\Facades\BackendMenu;
use System\Classes\SettingsManager;

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
    ];

    /**
     * @var string Form config
     */
    public $formConfig = 'config_form.yaml';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('October.System', 'system', 'settings');
        SettingsManager::setContext('Renatio.DynamicPDF', 'pdftemplates');
    }
}