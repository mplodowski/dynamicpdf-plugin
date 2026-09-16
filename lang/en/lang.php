<?php

return [
    'plugin' => [
        'name' => 'DynamicPDF',
        'description' => 'Generate PDFs from HTML and Twig templates edited in the backend, with layouts, page numbers, password protection and a file ready to attach.',
    ],
    'templates' => [
        'label' => 'Templates',
        'code' => 'Code',
        'title' => 'Title',
        'description' => 'Description',
        'layout' => 'Layout',
        'empty_option' => '-- No layout --',
        'code_comment' => 'Unique code used to refer to this template',
        'content_html' => 'HTML',
        'content_css' => 'CSS/LESS',
        'name' => 'Name',
        'return' => 'Return to templates list',
        'new_template' => 'New Template',
        'new_layout' => 'New Layout',
        'templates' => 'Templates',
        'layouts' => 'Layouts',
        'background_img' => 'Background Image',
        'preview_html' => 'Preview HTML',
        'preview_pdf' => 'Preview PDF',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
        'background_img_comment' => 'Use image with at least 96 DPI for correct display. Recommended 300 DPI.',
        'size' => 'Paper size',
        'orientation' => 'Paper orientation',
        'sample_data' => 'Sample data',
        'sample_data_comment' => 'JSON object with the variables the backend preview renders with, for example {"name": "John Doe", "items": [{"qty": 1, "price": 39}]}.',
        'is_custom' => 'Customized',
        'is_locked' => 'From view',
        'duplicate' => 'Duplicate',
        'duplicating' => 'Duplicating...',
        'duplicate_success' => 'Copy created.',
        'copy_suffix' => '(copy)',
    ],
    'template' => [
        'menu_label' => 'Template',
        'create_template' => 'Create Template',
        'edit_template' => 'Edit Template',
        'not_found' => 'Unable to find a registered template with code',
    ],
    'layouts' => [
        'label' => 'Layouts',
        'return' => 'Return to layouts list',
    ],
    'layout' => [
        'menu_label' => 'Layout',
        'create_layout' => 'Create Layout',
        'edit_layout' => 'Edit Layout',
        'not_found' => 'Unable to find a registered layout with code',
    ],
    'settings' => [
        'description' => 'Manage templates and layouts.',
    ],
    'permissions' => [
        'manage_templates' => 'Manage templates',
        'manage_layouts' => 'Manage layouts',
        'tab' => 'PDF',
    ],
    'menu' => [
        'label' => 'PDF Templates',
        'category' => 'PDF',
        'description' => 'Modify the PDF templates and manage PDF layouts.',
    ],
    'orientation' => [
        'portrait' => 'Portrait',
        'landscape' => 'Landscape',
    ],
    'options' => [
        'empty' => '-- choose --',
    ],
    'tab' => [
        'options' => 'Options',
    ],
    'demo' => [
        'enabled' => 'Demo is enabled. Please refresh PDF templates list.',
        'disabled' => 'Demo is disabled. Please refresh PDF templates list.',
    ],
];
