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
        'code_comment' => 'Code wird verwendet um die Vorlage eindeutig zu identifizieren.',
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
        'background_img_comment' => 'Bilder mit 96 DPI eignen sich am besten',
        'size' => 'Papierformat',
        'orientation' => 'Papierausrichtung',
    ],
    'template' => [
        'menu_label' => 'Vorlagen',
        'create_template' => 'Erstelle Vorlage',
        'edit_template' => 'Bearbeite Vorlage',
        'not_found' => 'Keine registrierte Vorlage mit dem Code gefunden',
    ],
    'layouts' => [
        'label' => 'Layouts',
        'return' => 'Zurück zu den Layouts',
    ],
    'layout' => [
        'menu_label' => 'Layout',
        'create_layout' => 'Erstelle Layout',
        'edit_layout' => 'Bearbeite Layout',
        'not_found' => 'Kein registriertes Layout mit dem Code gefunden',
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
        'category' => 'PDF',
        'description' => 'PDF-Vorlagen bearbeiten und PDF-Layouts verwalten.',
    ],
    'orientation' => [
        'portrait' => 'Hochformat',
        'landscape' => 'Querformat',
    ],
    'options' => [
        'empty' => '-- auswählen --',
    ],
    'tab' => [
        'options' => 'Optionen',
    ],
    'demo' => [
        'enabled' => 'Demo ist aktiviert. Bitte aktualisieren Sie die Liste der PDF-Vorlagen.',
        'disabled' => 'Demo ist deaktiviert. Bitte aktualisieren Sie die Liste der PDF-Vorlagen.',
    ],
];
