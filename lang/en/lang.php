<?php

return [
    'plugin'       => [
        'name'        => 'DynamicPDF',
        'description' => 'Generate dynamic and customized PDFs.',
    ],
    'pdftemplates' => [
        'label'                  => 'PDF Templates',
        'code'                   => 'Code',
        'title'                  => 'Title',
        'description'            => 'Description',
        'layout'                 => 'Layout',
        'empty_option'           => '-- No layout --',
        'code_comment'           => 'Unique code used to refer to this template',
        'content_html'           => 'HTML',
        'content_css'            => 'CSS',
        'name'                   => 'Name',
        'return_message'         => 'Return to pdf templates list',
        'new_template'           => 'New Template',
        'new_layout'             => 'New Layout',
        'templates'              => 'Templates',
        'layouts'                => 'Layouts',
        'delete_alert'           => 'Do you really want to delete this PDF template?',
        'background_img'         => 'Background Image',
        'preview'                => 'Preview',
        'created_at'             => 'Created at',
        'updated_at'             => 'Updated at',
        'background_img_comment' => 'Use image with 96 DPI for correct display'
    ],
    'pdftemplate'  => [
        'menu_label'      => 'PDF Template',
        'create_template' => 'Create PDF Template',
        'edit_template'   => 'Edit PDF Template',
    ],
    'pdflayouts'   => [
        'label'          => 'PDF Layouts',
        'return_message' => 'Return to pdf layouts list',
        'delete_alert'   => 'Do you really want to delete this PDF layout?',
    ],
    'pdflayout'    => [
        'menu_label'    => 'PDF Layout',
        'create_layout' => 'Create PDF Layout',
        'edit_layout'   => 'Edit PDF Layout',
    ],
    'settings'     => [
        'description' => 'Manage pdf templates and layouts.',
    ],
    'permissions'  => [
        'label' => 'Manage PDF templates',
        'tab'   => 'PDF',
    ],
];