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
        'preview_pdf' => 'Náhled',
        'created_at' => 'Vytvořeno v',
        'updated_at' => 'Upraveno v',
        'background_img_comment' => 'Pro správné zobrazení použijte obrázek s 96 DPI'
    ],
    'template' => [
        'menu_label' => 'PDF Šablony',
        'create_template' => 'Vytvoření PDF šablony',
        'edit_template' => 'Úprava PDF šablony',
    ],
    'layouts' => [
        'label' => 'PDF Layouty',
        'return' => 'Zpět na seznam PDF layoutů',
    ],
    'layout' => [
        'menu_label' => 'PDF Layouty',
        'create_layout' => 'Vytvoření PDF layoutu',
        'edit_layout' => 'Úprava PDF layoutu',
    ],
    'settings' => [
        'description' => 'Správa PDF šablon a layoutů.',
    ],
    'permissions' => [
        'label' => 'Správa PDF šablon',
        'tab' => 'PDF',
    ],
    'menu' => [
        'label' => 'PDF'
    ]
];
