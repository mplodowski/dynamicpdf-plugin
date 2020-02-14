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
        'empty_option' => '-- nincs --',
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
    ],
    'template' => [
        'menu_label' => 'Sablon',
        'create_template' => 'Sablon létrehozása',
        'edit_template' => 'Sablon szerkesztése',
    ],
    'layouts' => [
        'label' => 'Elrendezések',
        'return' => 'Vissza az elrendezésekhez',
    ],
    'layout' => [
        'menu_label' => 'Elrendezés',
        'create_layout' => 'Elrendezés létrehozása',
        'edit_layout' => 'Elrendezés szerkesztése',
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
        'label' => 'PDF'
    ],
    'orientation' => [
        'portrait' => 'Álló',
        'landscape' => 'Fekvő',
    ],
    'options' => [
        'empty' => '-- válasszon --',
    ],
];
