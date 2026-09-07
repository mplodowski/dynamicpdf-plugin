<?php

return [
    'plugin' => [
        'name' => 'DynamicPDF',
        'description' => 'Genera PDF dinamici e personalizzati.',
    ],
    'templates' => [
        'label' => 'Modelli',
        'code' => 'Codice',
        'title' => 'Titolo',
        'description' => 'Descrizione',
        'layout' => 'Layout',
        'empty_option' => '-- Nessun layout --',
        'code_comment' => 'Codice univoco usato per riferirsi a questo modello',
        'content_html' => 'HTML',
        'content_css' => 'CSS/LESS',
        'name' => 'Nome',
        'return' => 'Torna all\'elenco dei modelli',
        'new_template' => 'Nuovo modello',
        'new_layout' => 'Nuovo layout',
        'templates' => 'Modelli',
        'layouts' => 'Layout',
        'background_img' => 'Immagine di sfondo',
        'preview_html' => 'Anteprima HTML',
        'preview_pdf' => 'Anteprima PDF',
        'created_at' => 'Creato il',
        'updated_at' => 'Aggiornato il',
        'background_img_comment' => 'Usa un\'immagine di almeno 96 DPI per una visualizzazione corretta. Consigliati 300 DPI.',
        'size' => 'Formato carta',
        'orientation' => 'Orientamento carta',
    ],
    'template' => [
        'menu_label' => 'Modello',
        'create_template' => 'Crea modello',
        'edit_template' => 'Modifica modello',
        'not_found' => 'Nessun modello registrato con il codice',
    ],
    'layouts' => [
        'label' => 'Layout',
        'return' => 'Torna all\'elenco dei layout',
    ],
    'layout' => [
        'menu_label' => 'Layout',
        'create_layout' => 'Crea layout',
        'edit_layout' => 'Modifica layout',
        'not_found' => 'Nessun layout registrato con il codice',
    ],
    'settings' => [
        'description' => 'Gestisci modelli e layout.',
    ],
    'permissions' => [
        'manage_templates' => 'Gestire i modelli',
        'manage_layouts' => 'Gestire i layout',
        'tab' => 'PDF',
    ],
    'menu' => [
        'label' => 'Modelli PDF',
        'category' => 'PDF',
        'description' => 'Modifica i modelli PDF e gestisci i layout PDF.',
    ],
    'orientation' => [
        'portrait' => 'Verticale',
        'landscape' => 'Orizzontale',
    ],
    'options' => [
        'empty' => '-- scegli --',
    ],
    'tab' => [
        'options' => 'Opzioni',
    ],
    'demo' => [
        'enabled' => 'La demo è attiva. Aggiorna l\'elenco dei modelli PDF.',
        'disabled' => 'La demo è disattivata. Aggiorna l\'elenco dei modelli PDF.',
    ],
];
