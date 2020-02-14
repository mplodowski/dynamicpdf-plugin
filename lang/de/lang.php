<?php

return [
    'plugin' => [
        'name' => 'DynamicPDF',
        'description' => 'Generiert dynamische und anpassbare PDFs.',
    ],
    'templates' => [
        'label' => 'Vorlagen',
        'code' => 'Code',
        'title' => 'Titel',
        'description' => 'Beschreibung',
        'layout' => 'Layout',
        'empty_option' => '-- Kein Layout --',
        'code_comment' => 'Code wird verwendet um die Vorlage eindeutig zu Identifizieren.',
        'content_html' => 'HTML',
        'content_css' => 'CSS',
        'name' => 'Name',
        'return' => 'Zurück zu den PDF Vorlagen',
        'new_template' => 'Neue Vorlage',
        'new_layout' => 'Neues Layout',
        'templates' => 'Vorlagen',
        'layouts' => 'Layouts',
        'background_img' => 'Hintergrundbild',
        'preview_html' => 'HTML Vorschau',
        'preview_pdf' => 'PDF Vorschau',
        'created_at' => 'Erstellt am',
        'updated_at' => 'Aktualisiert am',
        'background_img_comment' => 'Bilder mit 96 DPI eignen sich am Besten',
    ],
    'template' => [
        'menu_label' => 'Vorlagen',
        'create_template' => 'Erstelle Vorlage',
        'edit_template' => 'Bearbeite Vorlage',
    ],
    'layouts' => [
        'label' => 'Layouts',
        'return' => 'Zurük zu den Layouts',
    ],
    'layout' => [
        'menu_label' => 'Layout',
        'create_layout' => 'Erstelle Layout',
        'edit_layout' => 'Bearbeite Layout',
    ],
    'settings' => [
        'description' => 'Verwaltung von Vorlagen und Layouts.',
    ],
    'permissions' => [
        'manage_templates' => 'Verwalte Vorlagen',
        'manage_layouts' => 'Verwalte Layouts',
        'tab' => 'PDF',
    ],
    'menu' => [
        'label' => 'PDF',
    ],
];
