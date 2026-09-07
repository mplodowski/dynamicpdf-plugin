<?php

return [
    'plugin' => [
        'name' => 'Dinamikus PDF',
        'description' => 'Egyedi PDF fájlok generálása.',
    ],
    'templates' => [
        'label' => 'Sablonok',
        'code' => 'Kód',
        'title' => 'Cím',
        'description' => 'Leírás',
        'layout' => 'Elrendezés',
        'empty_option' => '-- Nincs elrendezés --',
        'code_comment' => 'Egyedi kódot adjon meg.',
        'content_html' => 'HTML',
        'content_css' => 'CSS',
        'name' => 'Név',
        'return' => 'Vissza a sablonokhoz',
        'new_template' => 'Új sablon',
        'new_layout' => 'Új elrendezés',
        'templates' => 'Sablonok',
        'layouts' => 'Elrendezések',
        'background_img' => 'Háttérkép',
        'preview_html' => 'HTML előnézet',
        'preview_pdf' => 'PDF előnézet',
        'created_at' => 'Létrehozva',
        'updated_at' => 'Módosítva',
        'background_img_comment' => 'A megfelelő megjelenéshez 96 DPI méretű képet használjon.',
        'size' => 'Papír mérete',
        'orientation' => 'Papír tájolása',
        'sample_data' => 'Mintaadatok',
        'sample_data_comment' => 'JSON objektum a változókkal, amelyekkel az admin előnézet renderelődik, például {"name": "Kovács János"}.',
        'is_custom' => 'Testreszabott',
        'is_locked' => 'Zárolt',
        'preview' => 'Előnézet',
        'duplicate' => 'Duplikálás',
        'duplicating' => 'Duplikálás...',
        'duplicate_success' => 'A másolat létrejött.',
    ],
    'template' => [
        'menu_label' => 'Sablon',
        'create_template' => 'Sablon létrehozása',
        'edit_template' => 'Sablon szerkesztése',
        'not_found' => 'Nem található regisztrált sablon ezzel a kóddal',
    ],
    'layouts' => [
        'label' => 'Elrendezések',
        'return' => 'Vissza az elrendezésekhez',
    ],
    'layout' => [
        'menu_label' => 'Elrendezés',
        'create_layout' => 'Elrendezés létrehozása',
        'edit_layout' => 'Elrendezés szerkesztése',
        'not_found' => 'Nem található regisztrált elrendezés ezzel a kóddal',
    ],
    'settings' => [
        'description' => 'Sablonok és elrendezések kezelése.',
    ],
    'permissions' => [
        'manage_templates' => 'Sablonok kezelése',
        'manage_layouts' => 'Elrendezések kezelése',
        'tab' => 'PDF',
    ],
    'menu' => [
        'label' => 'PDF',
        'category' => 'PDF',
        'description' => 'PDF sablonok szerkesztése és PDF elrendezések kezelése.',
    ],
    'orientation' => [
        'portrait' => 'Álló',
        'landscape' => 'Fekvő',
    ],
    'options' => [
        'empty' => '-- válasszon --',
    ],
    'tab' => [
        'options' => 'Beállítások',
    ],
    'demo' => [
        'enabled' => 'A demó be van kapcsolva. Frissítse a PDF sablonok listáját.',
        'disabled' => 'A demó ki van kapcsolva. Frissítse a PDF sablonok listáját.',
    ],
];
