<?php

return [
    'plugin' => [
        'name' => 'Dynamické PDF',
        'description' => 'Generování dynamických a upravitelných PDF.',
    ],
    'templates' => [
        'label' => 'PDF Šablony',
        'code' => 'Kód',
        'title' => 'Nadpis',
        'description' => 'Popis',
        'layout' => 'Layout',
        'empty_option' => '-- Bez šablony --',
        'code_comment' => 'Unikátní kód patřící pouze této šabloně',
        'content_html' => 'HTML',
        'content_css' => 'CSS',
        'name' => 'Jméno',
        'return' => 'Zpět na seznam šablon',
        'new_template' => 'Nová šablona',
        'new_layout' => 'Nový layout',
        'templates' => 'Šablony',
        'layouts' => 'Layouty',
        'background_img' => 'Obrázek pozadí',
        'preview_html' => 'Náhled HTML',
        'preview_pdf' => 'Náhled',
        'created_at' => 'Vytvořeno v',
        'updated_at' => 'Upraveno v',
        'background_img_comment' => 'Pro správné zobrazení použijte obrázek s 96 DPI',
        'size' => 'Formát papíru',
        'orientation' => 'Orientace papíru',
    ],
    'template' => [
        'menu_label' => 'PDF Šablony',
        'create_template' => 'Vytvoření PDF šablony',
        'edit_template' => 'Úprava PDF šablony',
        'not_found' => 'Registrovaná šablona s tímto kódem nebyla nalezena',
    ],
    'layouts' => [
        'label' => 'PDF Layouty',
        'return' => 'Zpět na seznam PDF layoutů',
    ],
    'layout' => [
        'menu_label' => 'PDF Layouty',
        'create_layout' => 'Vytvoření PDF layoutu',
        'edit_layout' => 'Úprava PDF layoutu',
        'not_found' => 'Registrované rozvržení s tímto kódem nebylo nalezeno',
    ],
    'settings' => [
        'description' => 'Správa PDF šablon a layoutů.',
    ],
    'permissions' => [
        'manage_templates' => 'Spravovat šablony',
        'manage_layouts' => 'Spravovat rozvržení',
        'tab' => 'PDF',
    ],
    'menu' => [
        'label' => 'PDF',
        'category' => 'PDF',
        'description' => 'Upravovat šablony PDF a spravovat rozvržení PDF.',
    ],
    'orientation' => [
        'portrait' => 'Na výšku',
        'landscape' => 'Na šířku',
    ],
    'options' => [
        'empty' => '-- vyberte --',
    ],
    'tab' => [
        'options' => 'Možnosti',
    ],
    'demo' => [
        'enabled' => 'Demo je zapnuto. Obnovte prosím seznam šablon PDF.',
        'disabled' => 'Demo je vypnuto. Obnovte prosím seznam šablon PDF.',
    ],
];
