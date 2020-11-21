<?php

namespace Renatio\DynamicPDF\Tests\Controllers;

use Backend\Classes\WidgetManager;
use Backend\Facades\BackendAuth;
use Backend\Models\User;
use Renatio\DynamicPDF\Tests\TestCase;

class ControllerTestCase extends TestCase
{

    protected $user;

    public $backendUri;

    public function setUp()
    {
        parent::setUp();

        $this->registerFormWidgets();

        $this->loginUser();

        $this->backendUri = config('cms.backendUri', 'backend');
    }

    protected function registerFormWidgets()
    {
        WidgetManager::instance()->registerFormWidgets(function ($manager) {
            $manager->registerFormWidget('Backend\FormWidgets\CodeEditor', [
                'label' => 'Code editor',
                'code' => 'codeeditor',
            ]);
            $manager->registerFormWidget('Backend\FormWidgets\Relation', [
                'label' => 'Relationship',
                'code' => 'relation',
            ]);
            $manager->registerFormWidget('Backend\FormWidgets\FileUpload', [
                'label' => 'File uploader',
                'code' => 'fileupload',
            ]);
        });
    }

    protected function loginUser()
    {
        $this->user = factory(User::class)->create();

        $this->user->setPermissionsAttribute(
            json_encode([
                'renatio.dynamicpdf.manage_templates' => 1,
                'renatio.dynamicpdf.manage_layouts' => 1,
            ])
        );

        BackendAuth::login($this->user);
    }
}
